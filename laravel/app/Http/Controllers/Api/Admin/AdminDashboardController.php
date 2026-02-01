<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminDashboardController extends Controller
{
    /* =========================================================
     * Helper
     * ======================================================= */

    private function tableBySource(string $source): ?string
    {
        $tables = [
            'mandiri' => 'pmb_registrations',
            'kip'     => 'pmb_kip_registrations',
            'yayasan' => 'pmb_yayasan_registrations',
        ];
        return $tables[$source] ?? null;
    }

private function urlPublic(?string $path): ?string
{
    if (!$path) return null;

    $p = trim($path);
    if ($p === '') return null;

    if (Str::startsWith($p, ['http://', 'https://'])) return $p;

    $p = preg_replace('#^public/#', '', $p);
    $p = preg_replace('#^storage/#', '', $p);

    /** @var FilesystemAdapter $disk */
    $disk = Storage::disk('public');

    // fallback kalau IDE/runtime aneh
    if (!method_exists($disk, 'url')) {
        return asset('storage/' . ltrim($p, '/'));
    }

    return $disk->url($p);
}



    private function decodeJsonArray($raw): array
    {
        if (is_array($raw)) return $raw;
        if (!is_string($raw) || trim($raw) === '') return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Tambahkan field url dari *_path jika *_url kosong.
     * Tambahkan list untuk:
     * - mandiri: berkas (JSON paths) => berkas_urls + berkas_files
     * - yayasan: file_rapor_paths (JSON paths) => file_rapor_urls
     */
    private function hydrateFileUrls(string $source, array $rowArr, string $table): array
    {
        // foto
        $fotoPath = $rowArr['foto_path'] ?? ($rowArr['nama_path'] ?? null);
        $rowArr['foto_url'] = $rowArr['foto_url'] ?? $this->urlPublic($fotoPath);

        // generate *_url dari *_path jika ada kolom url-nya
        foreach ($rowArr as $k => $v) {
            if (!is_string($k) || !Str::endsWith($k, '_path')) continue;

            $base = Str::replaceLast('_path', '', $k);
            $urlKey = $base . '_url';

            // jangan override kalau sudah ada
            if (array_key_exists($urlKey, $rowArr) && !empty($rowArr[$urlKey])) continue;

            // kalau tabel punya kolom *_url, kita isi
            if (Schema::hasColumn($table, $urlKey)) {
                $rowArr[$urlKey] = $this->urlPublic(is_string($v) ? $v : null);
            }
        }

        // mandiri: berkas (longtext JSON array)
        if ($source === 'mandiri' && Schema::hasColumn($table, 'berkas')) {
            $paths = $this->decodeJsonArray($rowArr['berkas'] ?? null);
            $urls = [];
            $files = [];
            foreach ($paths as $p) {
                if (!is_string($p) || trim($p) === '') continue;
                $u = $this->urlPublic($p);
                if ($u) {
                    $urls[] = $u;
                    $files[] = [
                        'name' => basename($p),
                        'path' => $p,
                        'url'  => $u,
                    ];
                }
            }
            $rowArr['berkas_urls']  = $urls;
            $rowArr['berkas_files'] = $files;
        }

        // yayasan: file_rapor_paths (longtext JSON array)
        if ($source === 'yayasan' && Schema::hasColumn($table, 'file_rapor_paths')) {
            $paths = $this->decodeJsonArray($rowArr['file_rapor_paths'] ?? null);
            $urls = [];
            foreach ($paths as $p) {
                if (!is_string($p) || trim($p) === '') continue;
                $u = $this->urlPublic($p);
                if ($u) $urls[] = $u;
            }
            $rowArr['file_rapor_urls'] = $urls;
        }

        return $rowArr;
    }

    /* =========================================================
     * DASHBOARD API
     * ======================================================= */

    // GET /api/admin/dashboard/summary
    public function summary(Request $request)
    {
        $mandiriTotal = DB::table('pmb_registrations')->count();
        $kipTotal     = DB::table('pmb_kip_registrations')->count();
        $yayasanTotal = DB::table('pmb_yayasan_registrations')->count();

        $mandiriPaid  = DB::table('pmb_registrations')->where('status_pembayaran', 'paid')->count();
        $kipPaid      = DB::table('pmb_kip_registrations')->where('status_pembayaran', 'paid')->count();
        $yayasanPaid  = DB::table('pmb_yayasan_registrations')->where('status_pembayaran', 'paid')->count();

        $mandiriPending = DB::table('pmb_registrations')->where('status_pembayaran', 'pending')->count();
        $kipPending     = DB::table('pmb_kip_registrations')->where('status_pembayaran', 'pending')->count();
        $yayasanPending = DB::table('pmb_yayasan_registrations')->where('status_pembayaran', 'pending')->count();

        $total   = $mandiriTotal + $kipTotal + $yayasanTotal;
        $paid    = $mandiriPaid + $kipPaid + $yayasanPaid;
        $pending = $mandiriPending + $kipPending + $yayasanPending;

        return response()->json([
            'total'   => $total,
            'mandiri' => $mandiriTotal,
            'yayasan' => $yayasanTotal,
            'kip'     => $kipTotal,
            'paid'    => $paid,
            'pending' => $pending,
        ]);
    }

    // GET /api/admin/registrations?jalur=all|mandiri|yayasan|kip
    public function registrations(Request $request)
    {
        $jalur = strtolower((string) $request->query('jalur', 'all'));
        if (!in_array($jalur, ['all', 'mandiri', 'yayasan', 'kip'], true)) {
            $jalur = 'all';
        }

        // Normalisasi field sesuai Vue Anda:
        // { source, id, jalur, nama, email, phone, program_studi_1, program_studi_2, status_pembayaran, created_at }
        $qMandiri = DB::table('pmb_registrations')->selectRaw("
            'mandiri' as source,
            id,
            'mandiri' as jalur,
            nama_lengkap as nama,
            alamat_email as email,
            nomor_hp as phone,
            program_studi_1,
            program_studi_2,
            status_pembayaran,
            created_at
        ");

        $qKip = DB::table('pmb_kip_registrations')->selectRaw("
            'kip' as source,
            id,
            'kip' as jalur,
            nama_lengkap as nama,
            alamat_email as email,
            nomor_hp as phone,
            program_studi_1,
            program_studi_2,
            status_pembayaran,
            created_at
        ");

        $qYayasan = DB::table('pmb_yayasan_registrations')->selectRaw("
            'yayasan' as source,
            id,
            'yayasan' as jalur,
            nama_lengkap as nama,
            alamat_email as email,
            nomor_hp as phone,
            program_studi_1,
            program_studi_2,
            status_pembayaran,
            created_at
        ");

        if ($jalur === 'mandiri') {
            return response()->json(['data' => $qMandiri->orderByDesc('created_at')->get()]);
        }
        if ($jalur === 'kip') {
            return response()->json(['data' => $qKip->orderByDesc('created_at')->get()]);
        }
        if ($jalur === 'yayasan') {
            return response()->json(['data' => $qYayasan->orderByDesc('created_at')->get()]);
        }

        $union = $qMandiri->unionAll($qKip)->unionAll($qYayasan);

        $rows = DB::query()
            ->fromSub($union, 'u')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $rows]);
    }

    /* =========================================================
     * PMB CRUD
     * ======================================================= */

    // GET /api/admin/pmb/{source}/{id}
    public function show(Request $request, $source, $id)
    {
        $table = $this->tableBySource($source);
        if (!$table) {
            return response()->json(['message' => 'Invalid source'], 422);
        }

        $row = DB::table($table)->where('id', $id)->first();
        if (!$row) return response()->json(['message' => 'Not found'], 404);

        $arr = (array) $row;
        $arr = $this->hydrateFileUrls($source, $arr, $table);

        return response()->json(['data' => $arr]);
    }

    // PUT/POST (multipart with _method=PUT) /api/admin/pmb/{source}/{id}
    public function update(Request $request, $source, $id)
    {
        $table = $this->tableBySource($source);
        if (!$table) {
            return response()->json(['message' => 'Invalid source'], 422);
        }

        $row = DB::table($table)->where('id', $id)->first();
        if (!$row) return response()->json(['message' => 'Not found'], 404);

        $rowArr = (array) $row;
        $columns = Schema::getColumnListing($table);

        // allowed base (akan di-intersect dengan kolom tabel)
        $allowedBase = [
            'nama_lengkap',
            'alamat_email',
            'nomor_hp',
            'program_studi_1',
            'program_studi_2',
            'otp_terverifikasi',
            'berkas_terunggah',
            'status_pembayaran',
            'username',
            'jalur_pendaftaran', // kip/yayasan
            'jalur',             // mandiri
            'jenis_kelamin',
            'tempat_lahir',
            'tanggal_lahir',
            'nik',
            'nomor_kk',
            'nama_sekolah',
            'npsn_sekolah',
            'nisn',
            'jenis_sekolah',
            'jurusan_sekolah',
            'kabkota_sekolah',   // kip
            'kota_sekolah',      // mandiri
            'tahun_lulus',
        ];

        // ambil input yang diizinkan
        $data = $request->only($allowedBase);

        // mapping agar UI Anda tetap sama:
        // - mandiri: pakai jalur (tabel) tetapi UI mengirim jalur_pendaftaran
        if ($source === 'mandiri') {
            if (!empty($data['jalur_pendaftaran']) && in_array('jalur', $columns, true)) {
                $data['jalur'] = $data['jalur_pendaftaran'];
            }
            unset($data['jalur_pendaftaran']);

            // UI kirim kabkota_sekolah, mandiri kolomnya kota_sekolah
            if (!empty($data['kabkota_sekolah']) && in_array('kota_sekolah', $columns, true)) {
                $data['kota_sekolah'] = $data['kabkota_sekolah'];
            }
            unset($data['kabkota_sekolah']);
        } else {
            // kip/yayasan tidak punya jalur (mandiri)
            unset($data['jalur']);
        }

        // intersect dengan kolom yang benar-benar ada
        $data = array_filter(
            $data,
            fn($v, $k) => in_array($k, $columns, true),
            ARRAY_FILTER_USE_BOTH
        );

        // Normalize booleans
        if ($request->has('otp_terverifikasi') && in_array('otp_terverifikasi', $columns, true)) {
            $data['otp_terverifikasi'] = $request->boolean('otp_terverifikasi');
        }
        if ($request->has('berkas_terunggah') && in_array('berkas_terunggah', $columns, true)) {
            $data['berkas_terunggah'] = $request->boolean('berkas_terunggah');
        }

        /* ---------------------------
         * FOTO HANDLING
         * Kolom Anda: foto_path + foto_nama (bukan foto_name)
         * Juga dukung legacy: nama_path + nama_foto
         * ------------------------- */
        $hasFotoPath = in_array('foto_path', $columns, true);
        $hasFotoNama = in_array('foto_nama', $columns, true);
        $hasNamaPath = in_array('nama_path', $columns, true);
        $hasNamaFoto = in_array('nama_foto', $columns, true);

        $oldFotoPath = null;
        if ($hasFotoPath && !empty($rowArr['foto_path'])) $oldFotoPath = $rowArr['foto_path'];
        if (!$oldFotoPath && $hasNamaPath && !empty($rowArr['nama_path'])) $oldFotoPath = $rowArr['nama_path'];

        // Hapus foto jika diminta (opsional)
        if ($request->boolean('remove_foto')) {
            if ($oldFotoPath) {
                try { Storage::disk('public')->delete($oldFotoPath); } catch (\Throwable $e) {}
            }
            if ($hasFotoPath) $data['foto_path'] = null;
            if ($hasFotoNama) $data['foto_nama'] = null;
            if ($hasNamaPath) $data['nama_path'] = null;
            if ($hasNamaFoto) $data['nama_foto'] = null;
        }

        // Upload foto baru
        if ($request->hasFile('foto')) {
            $file = $request->file('foto');

            if (!$file->isValid()) {
                return response()->json(['message' => 'File upload invalid'], 422);
            }

            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowedExt, true)) {
                return response()->json(['message' => 'Foto harus jpg/jpeg/png/webp'], 422);
            }
            if ($file->getSize() > 4 * 1024 * 1024) {
                return response()->json(['message' => 'Ukuran foto maksimal 4MB'], 422);
            }

            // hapus lama
            if ($oldFotoPath) {
                try { Storage::disk('public')->delete($oldFotoPath); } catch (\Throwable $e) {}
            }

            $storedName = Str::uuid()->toString() . '.' . $ext;
            $path = $file->storeAs("pmb/{$source}/foto", $storedName, 'public');

            if ($hasFotoPath) $data['foto_path'] = $path;
            if ($hasFotoNama) $data['foto_nama'] = $file->getClientOriginalName();

            if ($hasNamaPath) $data['nama_path'] = $path;
            if ($hasNamaFoto) $data['nama_foto'] = $file->getClientOriginalName();
        }

        /* ---------------------------
         * BERKAS[] HANDLING
         * - mandiri: kolom berkas (JSON array paths)
         * - yayasan: kolom file_rapor_paths (JSON array paths) jika ada
         * - kip: jika tidak ada kolom penampung, di-skip (agar tidak orphan)
         * ------------------------- */
        $newBerkas = $request->file('berkas', []);
        if (!is_array($newBerkas)) $newBerkas = [];

        if (count($newBerkas) > 0) {
            $storedPaths = [];

            foreach ($newBerkas as $bf) {
                if (!$bf || !$bf->isValid()) continue;

                // batas umum 10MB per file (ubah kalau perlu)
                if ($bf->getSize() > 10 * 1024 * 1024) {
                    return response()->json(['message' => 'Ukuran berkas maksimal 10MB per file'], 422);
                }

                $ext = strtolower($bf->getClientOriginalExtension() ?: 'bin');
                $storedName = Str::uuid()->toString() . '.' . $ext;
                $path = $bf->storeAs("pmb/{$source}/berkas", $storedName, 'public');
                $storedPaths[] = $path;
            }

            if (count($storedPaths) > 0) {
                // mandiri -> berkas
                if (in_array('berkas', $columns, true)) {
                    $existing = $this->decodeJsonArray($rowArr['berkas'] ?? null);
                    $merged = array_values(array_filter(array_merge($existing, $storedPaths)));
                    $data['berkas'] = json_encode($merged);
                    if (in_array('berkas_terunggah', $columns, true)) $data['berkas_terunggah'] = true;
                }
                // yayasan -> file_rapor_paths (multi)
                elseif (in_array('file_rapor_paths', $columns, true)) {
                    $existing = $this->decodeJsonArray($rowArr['file_rapor_paths'] ?? null);
                    $merged = array_values(array_filter(array_merge($existing, $storedPaths)));
                    $data['file_rapor_paths'] = json_encode($merged);
                    if (in_array('berkas_terunggah', $columns, true)) $data['berkas_terunggah'] = true;
                }
                // jika tidak ada kolom penampung, jangan simpan (menghindari orphan files)
            }
        }

        try {
            DB::table($table)->where('id', $id)->update($data);

            $updated = DB::table($table)->where('id', $id)->first();
            $uArr = (array) $updated;
            $uArr = $this->hydrateFileUrls($source, $uArr, $table);

            return response()->json([
                'message' => 'Updated',
                'data' => $uArr,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to update',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // DELETE /api/admin/pmb/{source}/{id}
    public function destroy(Request $request, $source, $id)
    {
        $table = $this->tableBySource($source);
        if (!$table) {
            return response()->json(['message' => 'Invalid source'], 422);
        }

        $row = DB::table($table)->where('id', $id)->first();
        if (!$row) return response()->json(['message' => 'Not found'], 404);

        // Try delete files based on common columns
        try {
            $cols = (array) $row;
            $paths = [];

            foreach ([
                'foto_path', 'nama_path',
                'kip_ktp_path', 'kip_kk_path',
                'file_ktp_path', 'file_kk_path',
            ] as $c) {
                if (!empty($cols[$c]) && is_string($cols[$c])) $paths[] = $cols[$c];
            }

            // mandiri: berkas json
            if (!empty($cols['berkas']) && is_string($cols['berkas'])) {
                $decoded = json_decode($cols['berkas'], true);
                if (is_array($decoded)) foreach ($decoded as $p) if (is_string($p)) $paths[] = $p;
            }

            // yayasan: multi rapor
            if (!empty($cols['file_rapor_paths']) && is_string($cols['file_rapor_paths'])) {
                $decoded = json_decode($cols['file_rapor_paths'], true);
                if (is_array($decoded)) foreach ($decoded as $p) if (is_string($p)) $paths[] = $p;
            }

            foreach ($paths as $p) {
                if ($p) Storage::disk('public')->delete($p);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        DB::table($table)->where('id', $id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

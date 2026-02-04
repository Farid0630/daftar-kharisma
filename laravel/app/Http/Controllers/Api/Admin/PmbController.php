<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class PmbController extends Controller
{
    private function resolveTable(string $source): ?string
    {
        return match ($source) {
            'mandiri' => 'pmb_registrations',
            'kip'     => 'pmb_kip_registrations',
            'yayasan' => 'pmb_yayasan_registrations',
            default   => null,
        };
    }

    private function ensureIntId($id): int
    {
        // FIX: trim agar "2 " tidak dianggap invalid
        $sid = trim((string) $id);

        if ($sid === '' || !ctype_digit($sid)) {
            abort(response()->json(['message' => 'Invalid id'], 422));
        }
        return (int) $sid;
    }

    private function normalizePublicDiskPath(?string $p): ?string
    {
        if (!$p) return null;
        $p = trim($p);
        if ($p === '') return null;

        if (preg_match('#^https?://#i', $p)) {
            $path = parse_url($p, PHP_URL_PATH) ?: '';
            $p = $path;
        }

        $p = preg_replace('#^/storage/#', '', $p);
        $p = preg_replace('#^storage/#', '', $p);
        $p = preg_replace('#^public/#', '', $p);
        $p = ltrim($p, '/');

        return $p ?: null;
    }

    public function show(string $source, $id)
    {
        $source = strtolower(trim($source));
        $id = $this->ensureIntId($id);

        $table = $this->resolveTable($source);
        if (!$table) return response()->json(['message' => 'Invalid source'], 422);

        $row = DB::table($table)->where('id', $id)->first();
        if (!$row) return response()->json(['message' => 'Record not found'], 404);

        $arr = (array) $row;
        $arr['source'] = $source;
        $arr['id'] = $id;

        if (array_key_exists('otp_terverifikasi', $arr)) $arr['otp_terverifikasi'] = (bool) $arr['otp_terverifikasi'];
        if (array_key_exists('berkas_terunggah', $arr)) $arr['berkas_terunggah'] = (bool) $arr['berkas_terunggah'];

        return response()->json(['data' => $arr]);
    }

    public function update(Request $request, string $source, $id)
    {
        $source = strtolower(trim($source));
        $id = $this->ensureIntId($id);

        $table = $this->resolveTable($source);
        if (!$table) return response()->json(['message' => 'Invalid source'], 422);

        Log::info('PMB UPDATE HIT', [
            'source' => $source,
            'id' => $id,
            'method' => $request->method(),
            'content_type' => $request->header('content-type'),
            'keys' => array_keys($request->all()),
            'files' => array_keys($request->allFiles()),
        ]);

        $row = DB::table($table)->where('id', $id)->first();
        if (!$row) return response()->json(['message' => 'Not found'], 404);

        $columns = Schema::getColumnListing($table);

        // 1) Buang keys yang tidak boleh disentuh
        $skipKeys = [
            '_token', '_method', 'source', 'id',
            'password',
            'created_at', 'updated_at',

            // runtime UI
            'berkas_new_files', 'berkas_field_files', 'berkas_remove_bases',
            'foto_file', 'foto_preview', 'foto_name',
        ];

        // ambil payload dasar (non-file)
        $data = Arr::except($request->all(), $skipKeys);

        // 2) Hanya kolom yang ada di tabel
        $data = Arr::only($data, $columns);

        // 3) HARD BLOCK kolom meta yang sering bikin masalah (double safety)
        $hardBlock = [
            'otp_verified_at',     // DATETIME (sering terkirim "0")
            'email_verified_at',
            'foto_url', 'foto_path',
        ];
        foreach ($hardBlock as $hb) {
            unset($data[$hb]);
        }

        // 4) Proteksi pola: *_path, *_url, *_paths, *_at tidak boleh diupdate dari request biasa
        foreach (array_keys($data) as $k) {
            if (str_ends_with($k, '_path') || str_ends_with($k, '_url') || str_ends_with($k, '_paths')) {
                unset($data[$k]);
                continue;
            }
            if (str_ends_with($k, '_at')) {
                unset($data[$k]);
                continue;
            }
        }

        // 5) Normalisasi: "" => null, array/object => JSON
        foreach ($data as $k => $v) {
            if ($v === '') $data[$k] = null;
            elseif (is_array($v) || is_object($v)) $data[$k] = json_encode($v);
        }

        // 6) Normalisasi boolean
        foreach (['otp_terverifikasi', 'otp_verifikasi', 'berkas_terunggah', 'setuju_syarat', 'setuju_kebenaran_data'] as $bk) {
            if (array_key_exists($bk, $data)) {
                $val = $data[$bk];
                $bool = filter_var($val, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
                $data[$bk] = $bool !== null ? $bool : (bool) ((int) $val);
            }
        }

        // 7) tanggal_lahir ke Y-m-d
        if (array_key_exists('tanggal_lahir', $data) && $data['tanggal_lahir']) {
            $v = $data['tanggal_lahir'];
            if (!(is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v))) {
                try {
                    $data['tanggal_lahir'] = \Carbon\Carbon::parse($v)->format('Y-m-d');
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }

        // ==========================
        // REMOVE FILES (remove_files[])
        // ==========================
        $remove = $request->input('remove_files', []);
        if (is_array($remove) && $remove) {
            $rowArr = (array) $row;

            foreach ($remove as $base) {
                $base = trim((string) $base);
                if ($base === '') continue;

                $colPath = "{$base}_path";
                $colUrl  = "{$base}_url";
                $colNama = "{$base}_nama";

                $oldPath = $rowArr[$colPath] ?? null;
                $oldUrl  = $rowArr[$colUrl] ?? null;

                $candidate = $this->normalizePublicDiskPath($oldPath) ?: $this->normalizePublicDiskPath($oldUrl);
                if ($candidate) {
                    try { Storage::disk('public')->delete($candidate); } catch (\Throwable $e) {}
                }

                if (in_array($colPath, $columns, true)) $data[$colPath] = null;
                if (in_array($colUrl,  $columns, true)) $data[$colUrl]  = null;
                if (in_array($colNama, $columns, true)) $data[$colNama] = null;
            }
        }

        // ==========================
        // FILE UPLOADS
        // ==========================

        // Foto (key: foto)
        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            $path = $request->file('foto')->store("pmb/{$source}/{$id}/foto", 'public');

            if (in_array('foto_path', $columns, true)) $data['foto_path'] = $path;
            if (in_array('foto_url',  $columns, true)) $data['foto_url']  = Storage::url($path);

            if (in_array('foto_nama', $columns, true)) {
                $data['foto_nama'] = $request->file('foto')->getClientOriginalName();
            }
        }

        // berkas[] (tambahan)
        if ($request->hasFile('berkas')) {
            $files = $request->file('berkas');
            if (is_array($files)) {
                $stored = [];
                foreach ($files as $f) {
                    if (!$f || !$f->isValid()) continue;
                    $stored[] = $f->store("pmb/{$source}/{$id}/berkas", 'public');
                }

                if ($stored && in_array('berkas', $columns, true)) {
                    $oldRaw = (string) (($row->berkas ?? '') ?: '[]');
                    $old = json_decode($oldRaw, true);
                    $old = is_array($old) ? $old : [];

                    $oldPaths = [];
                    foreach ($old as $it) {
                        if (is_string($it)) $oldPaths[] = $it;
                        elseif (is_array($it)) $oldPaths[] = $it['path'] ?? $it['url'] ?? null;
                    }
                    $oldPaths = array_values(array_filter($oldPaths));

                    $merged = array_values(array_filter(array_merge($oldPaths, $stored)));
                    $data['berkas'] = json_encode($merged);
                }
            }
        }

        // Replace file per base (input name = base)
        foreach ($request->allFiles() as $key => $file) {
            if ($key === 'foto' || $key === 'berkas') continue;
            if (is_array($file)) continue;
            if (!$file || !$file->isValid()) continue;

            $base = trim((string) $key);
            if ($base === '') continue;

            $path = $file->store("pmb/{$source}/{$id}/replace", 'public');

            $colPath = "{$base}_path";
            $colUrl  = "{$base}_url";
            $colNama = "{$base}_nama";

            if (in_array($colPath, $columns, true)) $data[$colPath] = $path;
            if (in_array($colUrl,  $columns, true)) $data[$colUrl]  = Storage::url($path);
            if (in_array($colNama, $columns, true)) $data[$colNama] = $file->getClientOriginalName();
        }

        try {
            DB::table($table)->where('id', $id)->update($data);
            $updated = DB::table($table)->where('id', $id)->first();

            return response()->json(['message' => 'Updated', 'data' => $updated]);
        } catch (\Throwable $e) {
            Log::error('PMB UPDATE FAILED', [
                'source' => $source,
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to update',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, string $source, $id)
    {
        $source = strtolower(trim($source));
        $id = $this->ensureIntId($id);

        $table = $this->resolveTable($source);
        if (!$table) return response()->json(['message' => 'Invalid source'], 422);

        $row = DB::table($table)->where('id', $id)->first();
        if (!$row) return response()->json(['message' => 'Not found'], 404);

        try {
            $cols = (array) $row;
            $paths = [];

            foreach ($cols as $k => $v) {
                if (!is_string($v) || trim($v) === '') continue;
                if (str_ends_with($k, '_path') || $k === 'foto_path') $paths[] = $v;
            }

            if (!empty($cols['berkas']) && is_string($cols['berkas'])) {
                $decoded = json_decode($cols['berkas'], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $it) {
                        if (is_string($it)) $paths[] = $it;
                        elseif (is_array($it)) $paths[] = $it['path'] ?? $it['url'] ?? null;
                    }
                }
            }

            if (!empty($cols['file_rapor_paths']) && is_string($cols['file_rapor_paths'])) {
                $decoded = json_decode($cols['file_rapor_paths'], true);
                if (is_array($decoded)) foreach ($decoded as $p) $paths[] = $p;
            }

            $paths = array_values(array_filter(array_map(fn($p) => $this->normalizePublicDiskPath($p), $paths)));

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

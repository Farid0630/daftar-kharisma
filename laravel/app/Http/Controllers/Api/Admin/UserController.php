<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /* =========================
       Helpers
    ========================= */

    private function normEmail(?string $v): string
    {
        return strtolower(trim((string) $v));
    }

    /**
     * Buat URL public untuk file yang disimpan di disk "public".
     * Asumsi: sudah `php artisan storage:link`
     * sehingga /storage -> storage/app/public
     */
    private function publicUrl(?string $path): ?string
    {
        if (!$path) return null;

        $p = trim((string) $path);
        if ($p === '') return null;

        // sudah full url
        if (Str::startsWith($p, ['http://', 'https://'])) return $p;

        // normalisasi prefix yang sering muncul
        $p = preg_replace('#^(public/|storage/)#', '', $p);
        $p = ltrim($p, '/');

        return asset('storage/' . $p);
    }

    private function decodeJsonArray($raw): array
    {
        if (is_array($raw)) return $raw;
        if (!is_string($raw) || trim($raw) === '') return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function normalizePathToPublic(?string $path): ?string
    {
        if (!$path) return null;
        $p = trim((string) $path);
        if ($p === '') return null;

        // jangan ubah kalau sudah URL
        if (Str::startsWith($p, ['http://', 'https://'])) return $p;

        $p = preg_replace('#^(public/|storage/)#', '', $p);
        return ltrim($p, '/');
    }

    /**
     * Map row PMB menjadi format yang konsisten untuk frontend
     */
    private function mapPmbRow(string $source, array $row): array
    {
        // boolean normalization
        if (array_key_exists('otp_terverifikasi', $row)) $row['otp_terverifikasi'] = (bool) $row['otp_terverifikasi'];
        if (array_key_exists('berkas_terunggah', $row)) $row['berkas_terunggah'] = (bool) $row['berkas_terunggah'];

        $row['source'] = $source;

        // normalisasi nama/email/phone
        $row['nama']  = $row['nama'] ?? ($row['nama_lengkap'] ?? ($row['username'] ?? null));
        $row['email'] = $row['email'] ?? ($row['alamat_email'] ?? null);
        $row['phone'] = $row['phone'] ?? ($row['nomor_hp'] ?? null);

        // foto_url dari foto_path / nama_path
        $fotoPath = $row['foto_path'] ?? ($row['nama_path'] ?? null);
        $row['foto_url'] = $row['foto_url'] ?? $this->publicUrl(is_string($fotoPath) ? $fotoPath : null);

        // samakan jalur_pendaftaran
        if ($source === 'mandiri') {
            if (!empty($row['jalur']) && empty($row['jalur_pendaftaran'])) {
                $row['jalur_pendaftaran'] = $row['jalur'];
            }
        }

        // buat URL berkas umum (kalau ada path)
        // KIP
        if ($source === 'kip') {
            if (!empty($row['kip_ktp_path']) && empty($row['kip_ktp_url'])) {
                $row['kip_ktp_url'] = $this->publicUrl($row['kip_ktp_path']);
            }
            if (!empty($row['kip_kk_path']) && empty($row['kip_kk_url'])) {
                $row['kip_kk_url'] = $this->publicUrl($row['kip_kk_path']);
            }
        }

        // YAYASAN
        if ($source === 'yayasan') {
            if (!empty($row['bukti_prestasi_path']) && empty($row['bukti_prestasi_url'])) {
                $row['bukti_prestasi_url'] = $this->publicUrl($row['bukti_prestasi_path']);
            }
            if (!empty($row['file_ktp_path']) && empty($row['file_ktp_url'])) {
                $row['file_ktp_url'] = $this->publicUrl($row['file_ktp_path']);
            }
            if (!empty($row['file_kk_path']) && empty($row['file_kk_url'])) {
                $row['file_kk_url'] = $this->publicUrl($row['file_kk_path']);
            }
            if (!empty($row['file_lk_path']) && empty($row['file_lk_url'])) {
                $row['file_lk_url'] = $this->publicUrl($row['file_lk_path']);
            }
        }

        // MANDIRI: berkas bisa json array
        if ($source === 'mandiri' && array_key_exists('berkas', $row)) {
            $list = $this->decodeJsonArray($row['berkas'] ?? null);
            $row['berkas_list'] = array_values(array_filter(array_map(function ($p) {
                if (!is_string($p)) return null;
                $p = $this->normalizePathToPublic($p);
                return $p ? $this->publicUrl($p) : null;
            }, $list)));
        }

        // YAYASAN: file_rapor_paths bisa json array
        if ($source === 'yayasan' && array_key_exists('file_rapor_paths', $row)) {
            $list = $this->decodeJsonArray($row['file_rapor_paths'] ?? null);
            $row['file_rapor_list'] = array_values(array_filter(array_map(function ($p) {
                if (!is_string($p)) return null;
                $p = $this->normalizePathToPublic($p);
                return $p ? $this->publicUrl($p) : null;
            }, $list)));
        }

        return $row;
    }

    private function safeDeletePublic(?string $path): void
    {
        $p = $this->normalizePathToPublic($path);
        if (!$p) return;

        try {
            Storage::disk('public')->delete($p);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Ambil semua akun PMB dari 3 tabel berdasarkan email (case-insensitive).
     * Dipakai oleh show() dan me()
     */
    private function findPmbAccountsByEmail(string $emailNorm): array
    {
        $pmbAccounts = [];

        // MANDIRI
        $mandiriRows = DB::table('pmb_registrations')
            ->whereRaw('LOWER(TRIM(alamat_email)) = ?', [$emailNorm])
            ->orderByDesc('id')
            ->get();

        foreach ($mandiriRows as $r) {
            $pmbAccounts[] = $this->mapPmbRow('mandiri', (array) $r);
        }

        // KIP
        $kipRows = DB::table('pmb_kip_registrations')
            ->whereRaw('LOWER(TRIM(alamat_email)) = ?', [$emailNorm])
            ->orderByDesc('id')
            ->get();

        foreach ($kipRows as $r) {
            $pmbAccounts[] = $this->mapPmbRow('kip', (array) $r);
        }

        // YAYASAN (BUGFIX: group OR agar tidak kebaca salah)
        $yayRows = DB::table('pmb_yayasan_registrations')
            ->where(function ($q) use ($emailNorm) {
                $q->whereRaw('LOWER(TRIM(alamat_email)) = ?', [$emailNorm])
                  ->orWhereRaw('LOWER(TRIM(username)) = ?', [$emailNorm]);
            })
            ->orderByDesc('id')
            ->get();

        foreach ($yayRows as $r) {
            $pmbAccounts[] = $this->mapPmbRow('yayasan', (array) $r);
        }

        return $pmbAccounts;
    }

    /* =========================
       CRUD
    ========================= */

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 50);
        $perPage = max(1, min(200, $perPage));

        $query = User::query()->orderByDesc('id');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%");
            });
        }

        return response()->json($query->paginate($perPage));
    }

    /**
     * GET /api/admin/users/{id}
     * Kembalikan user + pmb_accounts (3 tabel)
     */
    public function show($id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'User not found'], 404);

        $emailNorm = $this->normEmail($user->email);

        $out = $user->toArray();
        $out['pmb_accounts'] = [];

        try {
            $out['pmb_accounts'] = $this->findPmbAccountsByEmail($emailNorm);
        } catch (\Throwable $e) {
            // ignore, tetap return user
        }

        return response()->json(['data' => $out]);
    }

    /**
     * OPTIONAL: GET /api/admin/me
     * Jika Anda butuh lihat data admin yang login + pmb_accounts.
     * (Kalau tidak perlu, boleh hapus method ini.)
     */
    public function me(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        $emailNorm = $this->normEmail($user->email);

        $out = $user->toArray();
        $out['pmb_accounts'] = [];

        try {
            $out['pmb_accounts'] = $this->findPmbAccountsByEmail($emailNorm);
        } catch (\Throwable $e) {
            // ignore
        }

        return response()->json(['data' => $out]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'User not found'], 404);

        $data = $request->only(['name', 'email', 'role', 'is_admin']);

        $v = Validator::make($data, [
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'role' => ['nullable', 'string'],
            'is_admin' => ['nullable'],
        ]);

        if ($v->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $v->errors()
            ], 422);
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'] ?? null;

        $user->is_admin = $request->has('is_admin')
            ? (bool) $request->boolean('is_admin')
            : (bool) ($data['is_admin'] ?? false);

        $user->save();

        return response()->json([
            'message' => 'Updated',
            'data' => $user,
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'User not found'], 404);

        $emailNorm = $this->normEmail($user->email);

        try {
            /* ============ MANDIRI ============ */
            $mandiriRows = DB::table('pmb_registrations')
                ->whereRaw('LOWER(TRIM(alamat_email)) = ?', [$emailNorm])
                ->get();

            foreach ($mandiriRows as $r) {
                $r = (array) $r;

                $this->safeDeletePublic($r['foto_path'] ?? null);
                $this->safeDeletePublic($r['nama_path'] ?? null);

                $berkas = $this->decodeJsonArray($r['berkas'] ?? null);
                foreach ($berkas as $p) {
                    $this->safeDeletePublic(is_string($p) ? $p : null);
                }

                DB::table('pmb_registrations')->where('id', $r['id'])->delete();
            }

            /* ============ KIP ============ */
            $kipRows = DB::table('pmb_kip_registrations')
                ->whereRaw('LOWER(TRIM(alamat_email)) = ?', [$emailNorm])
                ->get();

            foreach ($kipRows as $r) {
                $r = (array) $r;

                $this->safeDeletePublic($r['foto_path'] ?? null);
                $this->safeDeletePublic($r['nama_path'] ?? null);

                $this->safeDeletePublic($r['kip_ktp_path'] ?? null);
                $this->safeDeletePublic($r['kip_kk_path'] ?? null);

                DB::table('pmb_kip_registrations')->where('id', $r['id'])->delete();
            }

            /* ============ YAYASAN ============ */
            $yayRows = DB::table('pmb_yayasan_registrations')
                ->where(function ($q) use ($emailNorm) {
                    $q->whereRaw('LOWER(TRIM(alamat_email)) = ?', [$emailNorm])
                      ->orWhereRaw('LOWER(TRIM(username)) = ?', [$emailNorm]);
                })
                ->get();

            foreach ($yayRows as $r) {
                $r = (array) $r;

                $this->safeDeletePublic($r['foto_path'] ?? null);
                $this->safeDeletePublic($r['nama_path'] ?? null);

                $this->safeDeletePublic($r['file_ktp_path'] ?? null);
                $this->safeDeletePublic($r['file_kk_path'] ?? null);
                $this->safeDeletePublic($r['file_lk_path'] ?? null);
                $this->safeDeletePublic($r['bukti_prestasi_path'] ?? null);

                $rapor = $this->decodeJsonArray($r['file_rapor_paths'] ?? null);
                foreach ($rapor as $p) {
                    $this->safeDeletePublic(is_string($p) ? $p : null);
                }

                DB::table('pmb_yayasan_registrations')->where('id', $r['id'])->delete();
            }
        } catch (\Throwable $e) {
            // ignore errors
        }

        $user->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function download(Request $request)
    {
        $users = User::orderByDesc('id')->get([
            'id', 'name', 'email', 'role', 'is_admin', 'created_at'
        ]);

        $response = new StreamedResponse(function () use ($users) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'name', 'email', 'role', 'is_admin', 'created_at']);

            foreach ($users as $u) {
                fputcsv($out, [
                    $u->id,
                    $u->name,
                    $u->email,
                    $u->role,
                    (int) $u->is_admin,
                    $u->created_at,
                ]);
            }

            fclose($out);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="users.csv"');

        return $response;
    }
}

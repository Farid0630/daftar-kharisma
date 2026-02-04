<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserController extends Controller
{
    private function normEmail(?string $v): string
    {
        return strtolower(trim((string) $v));
    }

    /**
     * Public URL untuk file di disk public.
     * Asumsi: php artisan storage:link
     */
    private function publicUrl(?string $path): ?string
    {
        if (!$path) return null;

        $p = trim((string) $path);
        if ($p === '') return null;

        if (Str::startsWith($p, ['http://', 'https://'])) return $p;

        // "public/xxx" atau "storage/xxx" -> "xxx"
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

    private function mapPmbRow(string $source, array $row): array
    {
        // source
        $row['source'] = $source;

        // nama, email, phone
        $row['nama']  = $row['nama'] ?? ($row['nama_lengkap'] ?? ($row['username'] ?? null));
        $row['email'] = $row['email'] ?? ($row['alamat_email'] ?? null);
        $row['phone'] = $row['phone'] ?? ($row['nomor_hp'] ?? null);

        // bool normalization
        if (array_key_exists('otp_terverifikasi', $row)) $row['otp_terverifikasi'] = (bool) $row['otp_terverifikasi'];
        if (array_key_exists('berkas_terunggah', $row)) $row['berkas_terunggah'] = (bool) $row['berkas_terunggah'];

        // foto_url dari foto_path / nama_path
        $fotoPath = $row['foto_path'] ?? ($row['nama_path'] ?? null);
        $row['foto_url'] = $row['foto_url'] ?? $this->publicUrl(is_string($fotoPath) ? $fotoPath : null);

        // jalur_pendaftaran
        if ($source === 'mandiri') {
            if (!empty($row['jalur']) && empty($row['jalur_pendaftaran'])) {
                $row['jalur_pendaftaran'] = $row['jalur'];
            }
        }

        // URL berkas khusus
        if ($source === 'kip') {
            if (!empty($row['kip_ktp_path'])) $row['kip_ktp_url'] = $this->publicUrl($row['kip_ktp_path']);
            if (!empty($row['kip_kk_path']))  $row['kip_kk_url']  = $this->publicUrl($row['kip_kk_path']);
        }

        if ($source === 'yayasan') {
            if (!empty($row['bukti_prestasi_path'])) $row['bukti_prestasi_url'] = $this->publicUrl($row['bukti_prestasi_path']);
            if (!empty($row['file_ktp_path'])) $row['file_ktp_url'] = $this->publicUrl($row['file_ktp_path']);
            if (!empty($row['file_kk_path']))  $row['file_kk_url']  = $this->publicUrl($row['file_kk_path']);
            if (!empty($row['file_lk_path']))  $row['file_lk_url']  = $this->publicUrl($row['file_lk_path']);
        }

        // mandiri berkas list
        if ($source === 'mandiri' && array_key_exists('berkas', $row)) {
            $list = $this->decodeJsonArray($row['berkas']);
            $row['berkas_list'] = array_values(array_filter(array_map(function ($p) {
                if (!is_string($p)) return null;
                return $this->publicUrl($p);
            }, $list)));
        }

        // yayasan rapor list
        if ($source === 'yayasan' && array_key_exists('file_rapor_paths', $row)) {
            $list = $this->decodeJsonArray($row['file_rapor_paths']);
            $row['file_rapor_list'] = array_values(array_filter(array_map(function ($p) {
                if (!is_string($p)) return null;
                return $this->publicUrl($p);
            }, $list)));
        }

        return $row;
    }

    /**
     * GET /api/user
     * Middleware: web + auth
     */
    public function me(Request $request)
    {
        $u = $request->user();
        if (!$u) return response()->json(['message' => 'Unauthenticated'], 401);

        $emailNorm = $this->normEmail($u->email);

        $pmbAccounts = [];

        // mandiri
        $mandiri = DB::table('pmb_registrations')
            ->whereRaw('LOWER(TRIM(alamat_email)) = ?', [$emailNorm])
            ->orderByDesc('id')
            ->get();

        foreach ($mandiri as $r) {
            $pmbAccounts[] = $this->mapPmbRow('mandiri', (array) $r);
        }

        // kip
        $kip = DB::table('pmb_kip_registrations')
            ->whereRaw('LOWER(TRIM(alamat_email)) = ?', [$emailNorm])
            ->orderByDesc('id')
            ->get();

        foreach ($kip as $r) {
            $pmbAccounts[] = $this->mapPmbRow('kip', (array) $r);
        }

        // yayasan (WAJIB group OR)
        $yayasan = DB::table('pmb_yayasan_registrations')
            ->where(function ($q) use ($emailNorm) {
                $q->whereRaw('LOWER(TRIM(alamat_email)) = ?', [$emailNorm])
                  ->orWhereRaw('LOWER(TRIM(username)) = ?', [$emailNorm]);
            })
            ->orderByDesc('id')
            ->get();

        foreach ($yayasan as $r) {
            $pmbAccounts[] = $this->mapPmbRow('yayasan', (array) $r);
        }

        $out = $u->toArray();
        $out['pmb_accounts'] = $pmbAccounts;

        return response()->json(['data' => $out]);
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminPmbController extends Controller
{
    private function tableFor(string $source): ?string
    {
        return match (strtolower(trim($source))) {
            'mandiri' => 'pmb_registrations',
            'kip'     => 'pmb_kip_registrations',
            'yayasan' => 'pmb_yayasan_registrations',
            default   => null,
        };
    }

    private function normalizeStoragePath(?string $path): ?string
    {
        if (!$path) return null;
        $p = trim($path);
        if ($p === '') return null;

        // contoh input: "public/pmb/berkas/a.pdf" atau "pmb/berkas/a.pdf" atau "/storage/pmb/berkas/a.pdf"
        $p = preg_replace('#^https?://[^/]+/storage/#', '', $p);
        $p = preg_replace('#^/storage/#', '', $p);
        $p = preg_replace('#^storage/#', '', $p);
        $p = preg_replace('#^public/#', '', $p);

        return ltrim($p, '/');
    }

    private function collectFiles(object $row): array
    {
        $files = [];

        foreach ((array) $row as $k => $v) {
            if (!is_string($v)) continue;

            // ambil semua kolom *_path termasuk foto_path, file_ktp_path, dst.
            if ($k === 'foto_path' || str_ends_with($k, '_path')) {
                $p = $this->normalizeStoragePath($v);
                if ($p) $files[] = $p;
            }
        }

        // mandiri: kolom "berkas" JSON array [{name,path,url}, ...]
        if (property_exists($row, 'berkas') && is_string($row->berkas) && trim($row->berkas) !== '') {
            $arr = json_decode($row->berkas, true);
            if (is_array($arr)) {
                foreach ($arr as $it) {
                    $cand = $it['path'] ?? $it['url'] ?? null;
                    $p = $this->normalizeStoragePath(is_string($cand) ? $cand : null);
                    if ($p) $files[] = $p;
                }
            }
        }

        // beberapa jalur menyimpan file_rapor_paths (json array / string)
        if (property_exists($row, 'file_rapor_paths')) {
            $v = $row->file_rapor_paths;
            $arr = [];

            if (is_string($v) && trim($v) !== '') {
                $decoded = json_decode($v, true);
                $arr = is_array($decoded) ? $decoded : [ $v ];
            } elseif (is_array($v)) {
                $arr = $v;
            }

            foreach ($arr as $pth) {
                $p = $this->normalizeStoragePath(is_string($pth) ? $pth : null);
                if ($p) $files[] = $p;
            }
        }

        // unik + rapi
        return array_values(array_unique(array_filter($files)));
    }

    public function destroy(string $source, string $id)
    {
        $table = $this->tableFor($source);
        if (!$table) {
            return response()->json(['message' => 'Source tidak valid.'], 422);
        }

        if (!preg_match('/^\d+$/', $id)) {
            return response()->json(['message' => 'ID tidak valid.'], 422);
        }

        $row = DB::table($table)->where('id', (int)$id)->first();
        if (!$row) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        // kumpulkan file yang terkait (best effort)
        $files = $this->collectFiles($row);

        DB::transaction(function () use ($table, $id) {
            DB::table($table)->where('id', (int)$id)->delete();
        });

        // hapus file setelah record terhapus (best effort, tidak menggagalkan response)
        foreach ($files as $p) {
            try {
                Storage::disk('public')->delete($p);
            } catch (\Throwable $e) {
                // optional: log($e)
            }
        }

        return response()->json(['message' => 'Record PMB berhasil dihapus.']);
    }
}

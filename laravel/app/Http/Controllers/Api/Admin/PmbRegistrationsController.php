<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PmbRegistrationsController extends Controller
{
    private function colExpr(string $table, array $candidates, string $fallback = 'NULL'): string
    {
        $cols = [];
        foreach ($candidates as $c) {
            if (Schema::hasColumn($table, $c)) {
                $cols[] = "`{$c}`";
            }
        }
        if (empty($cols)) return $fallback;
        return 'COALESCE(' . implode(', ', $cols) . ')';
    }

    private function nullableCol(string $table, string $col): string
    {
        return Schema::hasColumn($table, $col) ? "`{$col}`" : 'NULL';
    }

    private function buildQuery(string $table, string $source)
    {
        $nameExpr  = $this->colExpr($table, ['nama_lengkap', 'nama', 'name', 'username'], 'NULL');
        $emailExpr = $this->colExpr($table, ['alamat_email', 'email'], 'NULL');

        $otpExpr   = $this->nullableCol($table, 'otp_terverifikasi');
        $berkasExpr = $this->nullableCol($table, 'berkas_terunggah');
        $bayarExpr = $this->nullableCol($table, 'status_pembayaran');

        return DB::table($table)->selectRaw("
            `id` as id,
            {$nameExpr} as name,
            {$emailExpr} as email,
            '{$source}' as source,
            {$otpExpr} as otp_terverifikasi,
            {$berkasExpr} as berkas_terunggah,
            {$bayarExpr} as status_pembayaran,
            `created_at` as created_at
        ");
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 25);
        $perPage = max(1, min(200, $perPage));

        $mandiri = $this->buildQuery('pmb_registrations', 'mandiri');
        $kip     = $this->buildQuery('pmb_kip_registrations', 'kip');
        $yayasan = $this->buildQuery('pmb_yayasan_registrations', 'yayasan');

        $union = $mandiri->unionAll($kip)->unionAll($yayasan);

        $base = DB::query()
            ->fromSub($union, 'r')
            ->orderByDesc('created_at');

        return response()->json(
            $base->paginate($perPage)
        );
    }

    public function downloadCsv()
    {
        $mandiri = $this->buildQuery('pmb_registrations', 'mandiri');
        $kip     = $this->buildQuery('pmb_kip_registrations', 'kip');
        $yayasan = $this->buildQuery('pmb_yayasan_registrations', 'yayasan');

        $union = $mandiri->unionAll($kip)->unionAll($yayasan);

        $rows = DB::query()->fromSub($union, 'r')->orderByDesc('created_at')->get();

        $response = new StreamedResponse(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'id', 'name', 'email', 'source',
                'otp_terverifikasi', 'berkas_terunggah', 'status_pembayaran',
                'created_at'
            ]);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id,
                    $r->name,
                    $r->email,
                    $r->source,
                    (int) ((bool) $r->otp_terverifikasi),
                    (int) ((bool) $r->berkas_terunggah),
                    $r->status_pembayaran,
                    $r->created_at,
                ]);
            }
            fclose($out);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="pmb-registrations.csv"');
        return $response;
    }
}

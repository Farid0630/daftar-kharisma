<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistrationController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 25);
        $perPage = max(1, min(200, $perPage));

        // Kolom yang disamakan: id, name, email, source, created_at
        $mandiri = DB::table('pmb_registrations')
            ->selectRaw("
                id,
                COALESCE(nama_lengkap, nama, username) as name,
                alamat_email as email,
                'mandiri' as source,
                created_at
            ");

        $kip = DB::table('pmb_kip_registrations')
            ->selectRaw("
                id,
                COALESCE(nama_lengkap, nama, username) as name,
                alamat_email as email,
                'kip' as source,
                created_at
            ");

        $yayasan = DB::table('pmb_yayasan_registrations')
            ->selectRaw("
                id,
                COALESCE(nama_lengkap, nama, username) as name,
                alamat_email as email,
                'yayasan' as source,
                created_at
            ");

        // UNION ALL (gabung)
        $union = $mandiri->unionAll($kip)->unionAll($yayasan);

        // Penting: union perlu dibungkus fromSub agar bisa order/paginate
        $base = DB::query()->fromSub($union, 'r');

        if ($q !== '') {
            $base->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('source', 'like', "%{$q}%");
            });
        }

        $rows = $base
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($rows);
    }
}

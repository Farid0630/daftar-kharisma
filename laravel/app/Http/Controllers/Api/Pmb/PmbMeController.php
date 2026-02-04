<?php

namespace App\Http\Controllers\Api\Pmb;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// GANTI model sesuai nama model Anda
use App\Models\PmbRegistration;          // tabel pmb_registrations (mandiri)
use App\Models\PmbYayasanRegistration;   // tabel pmb_yayasan_registrations
use App\Models\PmbKipRegistration;       // tabel pmb_kip_registrations

class PmbMeController extends Controller
{
    public function me(Request $request)
    {
        // ====== AMBIL IDENTITAS ======
        // 1. Dari session PMB (jika ada)
        $email    = session('pmb_email');
        $username = session('pmb_username');

        // 2. Jika tidak ada PMB session, ambil dari auth user (untuk user biasa yang login)
        if (!$email && !$username && $request->user()) {
            $email = $request->user()->email;
        }

        if (!$email && !$username) {
            return response()->json([
                'message' => 'Session PMB tidak valid. Silakan login ulang.'
            ], 401);
        }

        // ====== CARI DI 3 TABEL ======
        // Catatan: di tabel Anda kolom email mayoritas bernama `alamat_email`
        // dan usernamenya ada `username` (kip/yayasan).
        $mandiri = PmbRegistration::query()
            ->when($email, fn($q) => $q->where('alamat_email', $email))
            ->when(!$email && $username, fn($q) => $q->where('username', $username))
            ->latest()
            ->first();

        $yayasan = PmbYayasanRegistration::query()
            ->when($email, fn($q) => $q->where('alamat_email', $email))
            ->when(!$email && $username, fn($q) => $q->where('username', $username))
            ->latest()
            ->first();

        $kip = PmbKipRegistration::query()
            ->when($email, fn($q) => $q->where('alamat_email', $email))
            ->when(!$email && $username, fn($q) => $q->where('username', $username))
            ->latest()
            ->first();

        // ====== PILIH DATA UTAMA (PRIORITAS) ======
        // Kalau jalur ada di session, pakai itu; kalau tidak, ambil yang ketemu dulu.
        $jalur = session('pmb_jalur'); // 'mandiri'|'yayasan'|'kip' (kalau Anda simpan)
        $chosen = null;
        $source = null;

        if ($jalur === 'mandiri' && $mandiri) {
            $chosen = $mandiri;
            $source = 'mandiri';
        } elseif ($jalur === 'yayasan' && $yayasan) {
            $chosen = $yayasan;
            $source = 'yayasan';
        } elseif ($jalur === 'kip' && $kip) {
            $chosen = $kip;
            $source = 'kip';
        } else {
            if ($mandiri) {
                $chosen = $mandiri;
                $source = 'mandiri';
            } elseif ($yayasan) {
                $chosen = $yayasan;
                $source = 'yayasan';
            } elseif ($kip) {
                $chosen = $kip;
                $source = 'kip';
            }
        }

        if (!$chosen) {
            return response()->json([
                'message' => 'Data pendaftar tidak ditemukan pada tabel mandiri/yayasan/kip.'
            ], 404);
        }

        // ====== NORMALISASI FIELD UNTUK FRONTEND ======
        // Agar Vue tidak bingung perbedaan nama kolom
        $payload = [
            'source' => $source,
            'id' => $chosen->id,
            'jalur' => $chosen->jalur_pendaftaran ?? $chosen->jalur ?? $source,

            'nama' => $chosen->nama_lengkap ?? $chosen->nama ?? null,
            'email' => $chosen->alamat_email ?? null,
            'phone' => $chosen->nomor_hp ?? null,

            'program_studi_1' => $chosen->program_studi_1 ?? null,
            'program_studi_2' => $chosen->program_studi_2 ?? null,

            'status_pembayaran' => $chosen->status_pembayaran ?? null,
            'otp_terverifikasi' => (bool)($chosen->otp_terverifikasi ?? false),
            'berkas_terunggah'  => (bool)($chosen->berkas_terunggah ?? false),

            'created_at' => $chosen->created_at,
            'updated_at' => $chosen->updated_at,

            // Optional: kirim juga data mentah dari ketiga tabel (kalau Anda mau tampilkan semuanya)
            'all' => [
                'mandiri' => $mandiri,
                'yayasan' => $yayasan,
                'kip' => $kip,
            ],
        ];

        return response()->json($payload);
    }
}

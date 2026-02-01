<?php

namespace App\Http\Controllers\Api\Pmb;

use App\Http\Controllers\Controller;
use App\Http\Requests\PmbKipRegisterRequest;
use App\Models\PmbKipRegistration;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PmbKipRegisterController extends Controller
{
    public function store(PmbKipRegisterRequest $request)
    {
        $validated = $request->validated();

        // OTP wajib sudah verified (mengikuti pola Anda)
        if (!$validated['otp_terverifikasi']) {
            return response()->json([
                'message' => 'OTP belum terverifikasi.',
            ], 422);
        }

        // KIP gratis -> paksa paid di backend (jangan percaya client)
        $statusPembayaran = 'paid';

        $registration = DB::transaction(function () use ($request, $validated, $statusPembayaran) {

            $phoneE164 = Phone::normalizeIdToE164(
                $validated['nomor_hp'],
                env('WA_COUNTRY_CODE', '62')
            );

            // ===== Foto (opsional) =====
            $fotoNama = null;
            $fotoPath = null;
            $fotoUrl  = null;
            if ($request->hasFile('foto')) {
                $foto = $request->file('foto');
                $fotoNama = $foto->getClientOriginalName();
                $fotoPath = $foto->store('pmb/kip/foto', 'public');
                $fotoUrl  = asset('storage/' . ltrim($fotoPath, '/'));
            }

            // ===== KTP (wajib) =====
            $ktp = $request->file('kip_ktp');
            $ktpNama = $ktp->getClientOriginalName();
            $ktpPath = $ktp->store('pmb/kip/ktp', 'public');
            $ktpUrl  = asset('storage/' . ltrim($ktpPath, '/'));

            // ===== KK (opsional sementara) =====
$kkNama = null;
$kkPath = null;
$kkUrl  = null;

if ($request->hasFile('kip_kk')) {
    $kk = $request->file('kip_kk');
    $kkNama = $kk->getClientOriginalName();
    $kkPath = $kk->store('pmb/kip/kk', 'public');
    $kkUrl  = asset('storage/' . ltrim($kkPath, '/'));
}

            // username auto dari email (opsional, untuk kebutuhan login)
            $base = Str::slug(Str::before($validated['alamat_email'], '@'));
            $username = $base ?: 'kip';
            $i = 1;
            while (PmbKipRegistration::where('username', $username)->exists()) {
                $username = ($base ?: 'kip') . $i;
                $i++;
            }

            return PmbKipRegistration::create([
                'jalur_pendaftaran' => 'KIP',

                // Data diri
                'nama_lengkap' => $validated['nama_lengkap'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'tempat_lahir' => $validated['tempat_lahir'],
                'tanggal_lahir' => $validated['tanggal_lahir'],
                'nik' => $validated['nik'],
                'nomor_kk' => $validated['nomor_kk'],

                'program_studi_1' => $validated['program_studi_1'],
                'program_studi_2' => $validated['program_studi_2'] ?? null,

                // Sekolah
                'nama_sekolah' => $validated['nama_sekolah'],
                'npsn_sekolah' => $validated['npsn_sekolah'],
                'nisn' => $validated['nisn'],
                'jenis_sekolah' => $validated['jenis_sekolah'],
                'jurusan_sekolah' => $validated['jurusan_sekolah'],
                'kabkota_sekolah' => $validated['kabkota_sekolah'],
                'tahun_lulus' => (int) $validated['tahun_lulus'],

                // Akun
                'username' => $username,
                'alamat_email' => $validated['alamat_email'],
                'nomor_hp' => $phoneE164,
                'kata_sandi_hash' => Hash::make($validated['kata_sandi']),

                // OTP
                'kode_otp' => $validated['kode_otp'] ?? null,
                'otp_terverifikasi' => (bool) $validated['otp_terverifikasi'],

                // Pembayaran (gratis)
                'status_pembayaran' => $statusPembayaran,

                // Foto
                'foto_nama' => $fotoNama,
                'foto_path' => $fotoPath,
                'foto_url'  => $fotoUrl,

                // Berkas
                'kip_ktp_nama' => $ktpNama,
                'kip_ktp_path' => $ktpPath,
                'kip_ktp_url'  => $ktpUrl,

                // 'kip_kk_nama' => $kkNama,
                // 'kip_kk_path' => $kkPath,
                // 'kip_kk_url'  => $kkUrl,

                'berkas_terunggah' => true,
            ]);
        });

        return response()->json([
            'message' => 'Pendaftaran KIP berhasil disimpan ke sistem.',
            'data' => $registration,
        ], 201);
    }
}

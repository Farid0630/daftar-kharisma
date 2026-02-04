<?php

namespace App\Http\Controllers\Api\Pmb;

use App\Http\Controllers\Controller;
use App\Http\Requests\PmbYayasanRegisterRequest;
use App\Models\PmbYayasanRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PmbYayasanRegisterController extends Controller
{
    public function store(PmbYayasanRegisterRequest $request)
    {
        $validated = $request->validated();

        // OTP wajib verified
        if (empty($validated['otp_terverifikasi'])) {
            return response()->json([
                'message' => 'OTP belum terverifikasi.',
            ], 422);
        }

        // Pembayaran wajib lunas
        if (($validated['status_pembayaran'] ?? 'pending') !== 'paid') {
            return response()->json([
                'message' => 'Status pembayaran belum LUNAS.',
            ], 422);
        }

        if (empty($validated['setuju_biaya_formulir'])) {
            return response()->json([
                'message' => 'Anda harus menyetujui biaya formulir.',
            ], 422);
        }

        $registration = DB::transaction(function () use ($request, $validated) {

            // ===== Foto (opsional) =====
            $fotoNama = null;
            $fotoPath = null;
            if ($request->hasFile('foto')) {
                $foto = $request->file('foto');
                $fotoNama = $foto->getClientOriginalName();
                $fotoPath = $foto->store('pmb/yayasan/foto', 'public');
            }

            // ===== Bukti Prestasi (wajib) =====
            $bukti = $request->file('bukti_prestasi');
            $buktiNama = $bukti->getClientOriginalName();
            $buktiPath = $bukti->store('pmb/yayasan/bukti_prestasi', 'public');

            // ===== Berkas: KTP, KK (wajib) =====
            $ktp = $request->file('file_ktp');
            $ktpNama = $ktp->getClientOriginalName();
            $ktpPath = $ktp->store('pmb/yayasan/ktp', 'public');

            $kk = $request->file('file_kk');
            $kkNama = $kk->getClientOriginalName();
            $kkPath = $kk->store('pmb/yayasan/kk', 'public');

            // ===== Rapor (multi file, wajib) =====
            $raporPaths = [];
            $raporFiles = (array) $request->file('file_rapor', []);
            foreach ($raporFiles as $f) {
                $raporPaths[] = $f->store('pmb/yayasan/rapor', 'public');
            }

            // ===== Simpan ke DB (HARUS sesuai migration Anda) =====
            return PmbYayasanRegistration::create([
                // STEP 1
                'jalur_pendaftaran' => $validated['jalur_pendaftaran'] ?? 'Beasiswa Yayasan',
                'program_studi'     => $validated['program_studi'] ?? ($validated['program_studi_1'] ?? null),
                'program_studi_1'   => $validated['program_studi_1'] ?? null,
                'program_studi_2'   => $validated['program_studi_2'] ?? null,
                'jenis_beasiswa'    => $validated['jenis_beasiswa'] ?? null,
                'kategori_prestasi' => $validated['kategori_prestasi'] ?? null,
                'deskripsi_prestasi'=> $validated['deskripsi_prestasi'] ?? null,
                'bukti_prestasi_nama' => $buktiNama,
                'bukti_prestasi_path' => $buktiPath,

                // STEP 2
                'nama_lengkap'     => $validated['nama_lengkap'],
                'jenis_kelamin'    => $validated['jenis_kelamin'],
                'tempat_lahir'     => $validated['tempat_lahir'],
                'tanggal_lahir'    => $validated['tanggal_lahir'],
                'kewarganegaraan'  => $validated['kewarganegaraan'],
                'foto_nama'        => $fotoNama,
                'foto_path'        => $fotoPath,

                // STEP 3
                'provinsi_sekolah' => $validated['provinsi_sekolah'],
                'jenis_sekolah'    => $validated['jenis_sekolah'],
                'nama_sekolah'     => $validated['nama_sekolah'],
                'jurusan_sekolah'  => $validated['jurusan_sekolah'],
                'kabkota_sekolah'  => $validated['kabkota_sekolah'],
                'tahun_lulus'      => (int) $validated['tahun_lulus'],

                // STEP 4 (PENTING: kolomnya kata_sandi_hash, bukan password)
                'username'         => $validated['username'],
                'kata_sandi_hash'  => Hash::make($validated['kata_sandi']),
                'nomor_hp'         => $validated['nomor_hp'],
                'alamat_email'     => $validated['alamat_email'],

                // STEP 5
                'otp_terverifikasi' => (bool) $validated['otp_terverifikasi'],

                // STEP 6
                'setuju_biaya_formulir' => (bool) $validated['setuju_biaya_formulir'],
                'metode_pembayaran'     => $validated['metode_pembayaran'] ?? null,
                'status_pembayaran'     => $validated['status_pembayaran'] ?? 'pending',

                // STEP 7
                'file_ktp_nama' => $ktpNama,
                'file_ktp_path' => $ktpPath,
                'file_kk_nama'  => $kkNama,
                'file_kk_path'  => $kkPath,

                // sesuai migration Anda:
                'file_rapor_nama'  => $validated['file_rapor_nama'] ?? null,
                'file_rapor_paths' => $raporPaths,

                'berkas_terunggah' => true,
            ]);
        });

        return response()->json([
            'message' => 'Pendaftaran Beasiswa Yayasan berhasil disimpan ke sistem.',
            'data' => $registration,
        ], 201);
    }
}





<?php

namespace App\Http\Controllers\Api\Pmb;

use App\Http\Controllers\Controller;
use App\Models\PmbRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PmbRegisterController extends Controller
{
    public function store(Request $request)
    {
        /**
         * Frontend WAJIB submit pakai multipart/form-data (FormData).
         * Field file yang terbaca:
         * - $request->file('foto')
         * - $request->file('berkas') => array of UploadedFile
         */

        $validated = $request->validate([
            // Data pribadi
            'nama_lengkap'     => ['required', 'string', 'max:255'],
            'jenis_kelamin'    => ['required', Rule::in(['L', 'P'])],
            'tempat_lahir'     => ['required', 'string', 'max:255'],
            'tanggal_lahir'    => ['required', 'date'],
            'program_studi_1'  => ['required', 'string', 'max:255'],
            'program_studi_2'  => ['required', 'string', 'max:255'],

            // Sekolah
            'nama_sekolah'     => ['required', 'string', 'max:255'],
            'jenis_sekolah'    => ['required', 'string', 'max:255'],
            'kota_sekolah'     => ['required', 'string', 'max:255'],
            'jurusan'          => ['required', 'string', 'max:255'],
            'tahun_lulus'      => ['required', 'integer', 'min:1985', 'max:2026'],
            'nisn'             => ['nullable', 'string', 'max:32'],

            // Kontak & akun
            'alamat_email'     => ['required', 'email', 'max:255'],
            'nomor_hp'         => ['required', 'string', 'max:32'],
            'kata_sandi'       => ['required', 'string', 'min:6'],
            'konfirmasi_kata_sandi' => ['required', 'same:kata_sandi'],

            // OTP & pembayaran
            'otp_terverifikasi' => ['required', 'boolean'],
            'metode_pembayaran' => ['nullable', 'string', 'max:50'],
            'status_pembayaran' => ['required', Rule::in(['pending', 'paid'])],

            // Persetujuan
            'setuju_syarat'          => ['required', 'boolean'],
            'setuju_kebenaran_data'  => ['required', 'boolean'],

            // Upload
            'foto'        => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'], // 2MB

            // Jika Anda ingin WAJIB upload berkas (mandiri), aktifkan required + min
            'berkas'      => ['required', 'array', 'min:3'],
            'berkas.*'    => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'], // 4MB per file
        ]);

        if ($validated['program_studi_1'] === $validated['program_studi_2']) {
            return response()->json([
                'message' => 'Program studi pilihan 1 dan 2 tidak boleh sama.',
            ], 422);
        }

        if (!(bool) $validated['otp_terverifikasi']) {
            return response()->json([
                'message' => 'OTP belum terverifikasi.',
            ], 422);
        }

        if ($validated['status_pembayaran'] !== 'paid') {
            return response()->json([
                'message' => 'Status pembayaran belum LUNAS.',
            ], 422);
        }

        // ===== Simpan foto =====
        $fotoNama = null;
        $fotoPath = null;

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $fotoNama = $file->getClientOriginalName();
            $fotoPath = $file->store('pmb/foto', 'public'); // storage/app/public/pmb/foto/...
        }

        // ===== Simpan berkas (multi) =====
        $berkasJson = [];

        if ($request->hasFile('berkas')) {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('public');

            foreach ($request->file('berkas') as $f) {
                $path = $f->store('pmb/berkas', 'public');

                // url() valid di runtime Laravel; kalau storage link belum ada, URL tidak bisa diakses.
                $url = method_exists($disk, 'url')
                    ? $disk->url($path)
                    : asset('storage/' . ltrim($path, '/'));

                $berkasJson[] = [
                    'name' => $f->getClientOriginalName(),
                    'path' => $path,
                    'url'  => $url,
                ];
            }
        }

        $registration = PmbRegistration::create([
            'jalur'              => 'mandiri',

            'nama_lengkap'       => $validated['nama_lengkap'],
            'jenis_kelamin'      => $validated['jenis_kelamin'],
            'tempat_lahir'       => $validated['tempat_lahir'],
            'tanggal_lahir'      => $validated['tanggal_lahir'],
            'program_studi_1'    => $validated['program_studi_1'],
            'program_studi_2'    => $validated['program_studi_2'],

            'foto_nama'          => $fotoNama,
            'foto_path'          => $fotoPath,

            'nama_sekolah'       => $validated['nama_sekolah'],
            'jenis_sekolah'      => $validated['jenis_sekolah'],
            'kota_sekolah'       => $validated['kota_sekolah'],
            'jurusan'            => $validated['jurusan'],
            'tahun_lulus'        => $validated['tahun_lulus'],
            'nisn'               => $validated['nisn'] ?? null,

            'alamat_email'       => $validated['alamat_email'],
            'nomor_hp'           => $validated['nomor_hp'],
            'password'           => Hash::make($validated['kata_sandi']),

            'otp_terverifikasi'  => (bool) $validated['otp_terverifikasi'],
            'metode_pembayaran'  => $validated['metode_pembayaran'] ?? null,
            'status_pembayaran'  => $validated['status_pembayaran'],

            'setuju_syarat'         => (bool) $validated['setuju_syarat'],
            'setuju_kebenaran_data' => (bool) $validated['setuju_kebenaran_data'],

            'berkas_terunggah'   => count($berkasJson) > 0,
            'berkas'             => $berkasJson, // json
        ]);

        return response()->json([
            'message' => 'Pendaftaran berhasil disimpan ke sistem.',
            'data' => $registration,
        ]);
    }
}

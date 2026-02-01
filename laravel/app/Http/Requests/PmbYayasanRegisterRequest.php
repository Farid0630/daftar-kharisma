<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PmbYayasanRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // jalur & prodi
            'jalur_pendaftaran' => ['nullable', 'string', 'max:50'],
            'program_studi'     => ['nullable', 'string', 'max:255'],
            'program_studi_1'   => ['nullable', 'string', 'max:255'],
            'program_studi_2'   => ['nullable', 'string', 'max:255'],

            'jenis_beasiswa'    => ['required', 'in:akademik,non_akademik'],
            'kategori_prestasi' => ['nullable', 'array'],
            'kategori_prestasi.*' => ['string', 'max:120'],
            'deskripsi_prestasi' => ['nullable', 'string', 'max:2000'],

            // file bukti prestasi (wajib)
            'bukti_prestasi' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],

            // data diri
            'nama_lengkap'    => ['required', 'string', 'max:255'],
            'jenis_kelamin'   => ['required', 'in:L,P'],
            'tempat_lahir'    => ['required', 'string', 'max:120'],
            'tanggal_lahir'   => ['required', 'date'],
            'kewarganegaraan' => ['required', 'in:WNI,WNA'],

            // foto (opsional)
            'foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:4096'],

            // sekolah
            'provinsi_sekolah' => ['required', 'string', 'max:100'],
            'jenis_sekolah'    => ['required', 'string', 'max:10'],
            'nama_sekolah'     => ['required', 'string', 'max:255'],
            'jurusan_sekolah'  => ['required', 'string', 'max:100'],
            'kabkota_sekolah'  => ['required', 'string', 'max:120'],
            'tahun_lulus'      => ['required', 'integer', 'min:1995', 'max:2100'],

            // akun
            'username' => ['required', 'string', 'max:50', 'unique:pmb_yayasan_registrations,username'],
            'alamat_email' => ['required', 'email', 'max:255', 'unique:pmb_yayasan_registrations,alamat_email'],
            'nomor_hp' => ['required', 'string', 'max:30', 'unique:pmb_yayasan_registrations,nomor_hp'],

            'kata_sandi' => ['required', 'string', 'min:6', 'max:100'],
            'konfirmasi_kata_sandi' => ['required', 'same:kata_sandi'],

            // otp
            'otp_terverifikasi' => ['required', 'boolean'],
            'kode_otp' => ['nullable', 'string', 'max:10'], // tidak disimpan ke tabel, tapi boleh dikirim

            // pembayaran
            'setuju_biaya_formulir' => ['required', 'boolean'],
            'metode_pembayaran' => ['nullable', 'in:bank,ewallet'],
            'status_pembayaran' => ['required', 'in:pending,paid'],

            // berkas
            'file_ktp' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'file_kk'  => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],

            // multi file rapor
            'file_rapor' => ['required'],
            'file_rapor.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],

            // ringkas (opsional) - sesuai migration
            'file_rapor_nama' => ['nullable', 'string', 'max:255'],

            'berkas_terunggah' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'bukti_prestasi.required' => 'Mohon upload minimal satu bukti prestasi.',
            'metode_pembayaran.in' => 'Metode pembayaran tidak valid.',
            'file_rapor.required' => 'Mohon upload rapor kelas X & XI.',
            'konfirmasi_kata_sandi.same' => 'Kata sandi dan konfirmasi kata sandi tidak sama.',
        ];
    }
}

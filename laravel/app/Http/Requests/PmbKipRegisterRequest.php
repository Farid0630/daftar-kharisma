<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PmbKipRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jalur_pendaftaran' => ['nullable', 'string', 'max:50'],

            // Data diri
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', Rule::in(['L','P'])],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date'],
            'nik' => ['required', 'digits:16', 'unique:pmb_kip_registrations,nik'],
            'nomor_kk' => ['required', 'digits:16'],

            'program_studi_1' => ['required', 'string', 'max:100'],
            'program_studi_2' => ['nullable', 'string', 'max:100', 'different:program_studi_1'],

            // Sekolah
            'nama_sekolah' => ['required', 'string', 'max:255'],
            'npsn_sekolah' => ['required', 'string', 'max:20'],
            'nisn' => ['required', 'string', 'max:20'],
            'jenis_sekolah' => ['required', 'string', 'max:30'],
            'jurusan_sekolah' => ['required', 'string', 'max:80'],
            'kabkota_sekolah' => ['required', 'string', 'max:120'],
            'tahun_lulus' => ['required', 'integer', 'min:2000', 'max:2100'],

            // Akun
            'alamat_email' => ['required', 'email', 'max:255', 'unique:pmb_kip_registrations,alamat_email'],
            'nomor_hp' => ['required', 'string', 'max:30', 'unique:pmb_kip_registrations,nomor_hp'],
            'kata_sandi' => ['required', 'string', 'min:8'],
            'konfirmasi_kata_sandi' => ['required', 'same:kata_sandi'],

            // OTP
            'kode_otp' => ['nullable', 'string', 'max:10'],
            'otp_terverifikasi' => ['required', 'accepted'],

            // Upload
            'foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
            'kip_ktp' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'kip_kk' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'otp_terverifikasi.accepted' => 'Mohon verifikasi OTP WhatsApp terlebih dahulu.',
            'konfirmasi_kata_sandi.same' => 'Kata sandi dan konfirmasi kata sandi tidak sama.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PmbRegisterMandiriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Data pribadi
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'tempat_lahir' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date'],

            'program_studi_1' => ['required', 'string', 'max:120'],
            'program_studi_2' => ['required', 'string', 'max:120', 'different:program_studi_1'],

            // Foto (opsional) - kalau Anda kirim base64 dari front-end
            'foto_nama' => ['nullable', 'string', 'max:255'],
            'foto_preview' => ['nullable', 'string'], // data:image/...;base64,...

            // Sekolah
            'nama_sekolah' => ['required', 'string', 'max:255'],
            'jenis_sekolah' => ['required', 'string', 'max:50'],
            'kota_sekolah' => ['required', 'string', 'max:255'],
            'jurusan' => ['required', 'string', 'max:120'],
            'tahun_lulus' => ['required', 'integer', 'between:1985,2026'],

            // Mandiri: nisn opsional (karena Anda minta hapus nisn)
            'nisn' => ['nullable', 'string', 'max:30'],

            // Akun
            'alamat_email' => ['required', 'email', 'max:255'],
            'nomor_hp' => ['required', 'string', 'max:30'],
            'kata_sandi' => ['required', 'string', 'min:8'],
            'konfirmasi_kata_sandi' => ['required', 'same:kata_sandi'],

            // OTP (sementara ikut dari front-end)
            'otp_terverifikasi' => ['required', 'boolean', 'in:1,true'],

            // Pembayaran
            'metode_pembayaran' => ['nullable', 'string', 'max:50'],
            'status_pembayaran' => ['required', Rule::in(['pending', 'paid'])],

            // Persetujuan & berkas
            'setuju_syarat' => ['required', 'boolean', 'in:1,true'],
            'setuju_kebenaran_data' => ['required', 'boolean', 'in:1,true'],
            'berkas_terunggah' => ['required', 'boolean', 'in:1,true'],

            // Optional list berkas
            'berkas' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'program_studi_2.different' => 'Program Studi Pilihan 2 tidak boleh sama dengan Pilihan 1.',
            'konfirmasi_kata_sandi.same' => 'Konfirmasi kata sandi harus sama dengan kata sandi.',
            'otp_terverifikasi.in' => 'OTP WhatsApp wajib diverifikasi sebelum melanjutkan.',
            'status_pembayaran.in' => 'Status pembayaran harus pending atau paid.',
            'setuju_syarat.in' => 'Anda wajib menyetujui syarat & ketentuan.',
            'setuju_kebenaran_data.in' => 'Anda wajib menyetujui kebenaran data.',
            'berkas_terunggah.in' => 'Anda wajib mengunggah minimal satu berkas.',
        ];
    }
}

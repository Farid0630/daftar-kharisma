<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PmbYayasanRegistration extends Model
{
    protected $table = 'pmb_yayasan_registrations';

    protected $fillable = [
        'jalur_pendaftaran',
        'program_studi',
        'program_studi_1',
        'program_studi_2',
        'jenis_beasiswa',
        'kategori_prestasi',
        'deskripsi_prestasi',
        'bukti_prestasi_nama',
        'bukti_prestasi_path',

        'nama_lengkap',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'kewarganegaraan',
        'foto_nama',
        'foto_path',

        'provinsi_sekolah',
        'jenis_sekolah',
        'nama_sekolah',
        'jurusan_sekolah',
        'kabkota_sekolah',
        'tahun_lulus',

        'username',
        'kata_sandi_hash',
        'nomor_hp',
        'alamat_email',

        'otp_terverifikasi',

        'setuju_biaya_formulir',
        'metode_pembayaran',
        'status_pembayaran',

        'file_ktp_nama',
        'file_ktp_path',
        'file_kk_nama',
        'file_kk_path',

        'file_rapor_nama',
        'file_rapor_paths',

        'berkas_terunggah',
    ];

    protected $casts = [
        'kategori_prestasi' => 'array',
        'file_rapor_paths' => 'array',
        'otp_terverifikasi' => 'boolean',
        'setuju_biaya_formulir' => 'boolean',
        'berkas_terunggah' => 'boolean',
        'tanggal_lahir' => 'date',
    ];
}

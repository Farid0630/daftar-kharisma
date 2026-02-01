<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PmbKipRegistration extends Model
{
    protected $fillable = [
        'jalur_pendaftaran',

        'nama_lengkap',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'nik',
        'nomor_kk',

        'program_studi_1',
        'program_studi_2',

        'nama_sekolah',
        'npsn_sekolah',
        'nisn',
        'jenis_sekolah',
        'jurusan_sekolah',
        'kabkota_sekolah',
        'tahun_lulus',

        'username',
        'alamat_email',
        'nomor_hp',
        'kata_sandi_hash',

        'kode_otp',
        'otp_terverifikasi',

        'status_pembayaran',

        'foto_nama',
        'foto_path',
        'foto_url',

        'kip_ktp_nama',
        'kip_ktp_path',
        'kip_ktp_url',

        'kip_kk_nama',
        'kip_kk_path',
        'kip_kk_url',

        'berkas_terunggah',
    ];

    protected $casts = [
        'otp_terverifikasi' => 'boolean',
        'berkas_terunggah' => 'boolean',
        'tanggal_lahir' => 'date',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;

class PmbRegistration extends Model
{
    protected $table = 'pmb_registrations';

    protected $fillable = [
        'jalur',

        'nama_lengkap','jenis_kelamin','tempat_lahir','tanggal_lahir',
        'program_studi_1','program_studi_2',

        'foto_nama','foto_path',

        'nama_sekolah','jenis_sekolah','kota_sekolah','jurusan','tahun_lulus','nisn',

        'alamat_email','nomor_hp','password',

        'otp_terverifikasi','otp_verified_at',

        'metode_pembayaran','status_pembayaran',

        // OPTIONAL (pastikan kolom ada di DB)
        'xendit_external_id','xendit_invoice_id','xendit_invoice_url','xendit_expiry_date',

        'setuju_syarat','setuju_kebenaran_data',
        'berkas_terunggah','berkas',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'otp_terverifikasi' => 'boolean',
        'setuju_syarat' => 'boolean',
        'setuju_kebenaran_data' => 'boolean',
        'berkas_terunggah' => 'boolean',
        'berkas' => 'array',
        'otp_verified_at' => 'datetime',

        // OPTIONAL
        'xendit_expiry_date' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    protected $appends = [
        'foto_url',
    ];

    public function getFotoUrlAttribute(): ?string
    {
        if (!$this->foto_path) return null;

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        // url() tersedia di FilesystemAdapter, ini juga bikin Intelephense tidak protes
        return $disk->url($this->foto_path);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pmb_registrations', function (Blueprint $table) {
            $table->id();

            // jalur (mandiri/kip/yayasan) - untuk sekarang fokus mandiri
            $table->string('jalur')->default('mandiri');

            // Data pribadi
            $table->string('nama_lengkap');
            $table->enum('jenis_kelamin', ['L', 'P'])->default('L');
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');

            $table->string('program_studi_1');
            $table->string('program_studi_2');

            // Foto (opsional)
            $table->string('foto_nama')->nullable();
            $table->string('foto_path')->nullable();

            // Sekolah asal (mandiri: TANPA npsn; nisn opsional)
            $table->string('nama_sekolah');
            $table->string('jenis_sekolah');
            $table->string('kota_sekolah');
            $table->string('jurusan');
            $table->unsignedSmallInteger('tahun_lulus');
            $table->string('nisn')->nullable();

            // Kontak & akun
            $table->string('alamat_email');
            $table->string('nomor_hp');
            $table->string('password');

            // OTP
            $table->boolean('otp_terverifikasi')->default(false);
            $table->timestamp('otp_verified_at')->nullable();

            // Pembayaran
            $table->string('metode_pembayaran')->nullable();
            $table->enum('status_pembayaran', ['pending', 'paid'])->default('pending');

            // Berkas + persetujuan
            $table->boolean('setuju_syarat')->default(false);
            $table->boolean('setuju_kebenaran_data')->default(false);
            $table->boolean('berkas_terunggah')->default(false);

            // Optional: simpan daftar berkas kalau Anda pakai upload terpisah
            $table->json('berkas')->nullable();

            $table->timestamps();

            // Optional index
            $table->index('jalur');
            $table->index('alamat_email');
            $table->index('nomor_hp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pmb_registrations');
    }
};

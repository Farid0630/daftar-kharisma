<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pmb_kip_registrations', function (Blueprint $table) {
            $table->id();

            $table->string('jalur_pendaftaran')->default('KIP');

            // Data diri
            $table->string('nama_lengkap');
            $table->enum('jenis_kelamin', ['L','P'])->default('L');
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->string('nik', 16)->unique();
            $table->string('nomor_kk', 16);

            // Prodi
            $table->string('program_studi_1')->nullable();
            $table->string('program_studi_2')->nullable();

            // Sekolah
            $table->string('nama_sekolah');
            $table->string('npsn_sekolah', 20);
            $table->string('nisn', 20);
            $table->string('jenis_sekolah', 30);
            $table->string('jurusan_sekolah', 80);
            $table->string('kabkota_sekolah', 120);
            $table->unsignedSmallInteger('tahun_lulus');

            // Akun
            $table->string('username')->unique();
            $table->string('alamat_email')->unique();
            $table->string('nomor_hp')->unique();
            $table->string('kata_sandi_hash');

            // OTP
            $table->string('kode_otp')->nullable();
            $table->boolean('otp_terverifikasi')->default(false);

            // Pembayaran (gratis)
            $table->enum('status_pembayaran', ['pending','paid'])->default('paid');

            // Foto opsional
            $table->string('foto_nama')->nullable();
            $table->string('foto_path')->nullable();
            $table->text('foto_url')->nullable();

            // Berkas KIP
            $table->string('kip_ktp_nama')->nullable();
            $table->string('kip_ktp_path')->nullable();
            $table->text('kip_ktp_url')->nullable();

            $table->string('kip_kk_nama')->nullable();
            $table->string('kip_kk_path')->nullable();
            $table->text('kip_kk_url')->nullable();

            $table->boolean('berkas_terunggah')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pmb_kip_registrations');
    }
};

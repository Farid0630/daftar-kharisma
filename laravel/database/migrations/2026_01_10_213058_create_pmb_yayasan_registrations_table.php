<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pmb_yayasan_registrations', function (Blueprint $table) {
            $table->id();

            // STEP 1 - Jalur & Prodi & Prestasi
            $table->string('jalur_pendaftaran')->default('Beasiswa Yayasan');
            $table->string('program_studi')->nullable();     // backward compatible (utama)
            $table->string('program_studi_1')->nullable();
            $table->string('program_studi_2')->nullable();
            $table->enum('jenis_beasiswa', ['akademik', 'non_akademik'])->nullable();
            $table->json('kategori_prestasi')->nullable();
            $table->text('deskripsi_prestasi')->nullable();

            $table->string('bukti_prestasi_nama')->nullable();
            $table->string('bukti_prestasi_path')->nullable();

            // STEP 2 - Data Diri
            $table->string('nama_lengkap');
            $table->enum('jenis_kelamin', ['L', 'P'])->default('L');
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->enum('kewarganegaraan', ['WNI', 'WNA']);

            $table->string('foto_nama')->nullable();
            $table->string('foto_path')->nullable();

            // STEP 3 - Data Sekolah
            $table->string('provinsi_sekolah');
            $table->string('jenis_sekolah'); // SMA/SMK/MA
            $table->string('nama_sekolah');
            $table->string('jurusan_sekolah');
            $table->string('kabkota_sekolah');
            $table->unsignedSmallInteger('tahun_lulus');

            // STEP 4 - Akun & WA
            $table->string('username')->unique();
            $table->string('kata_sandi_hash');
            $table->string('nomor_hp')->unique();
            $table->string('alamat_email')->unique();

            // STEP 5 - OTP
            $table->boolean('otp_terverifikasi')->default(false);

            // STEP 6 - Pembayaran
            $table->boolean('setuju_biaya_formulir')->default(false);
            $table->string('metode_pembayaran')->nullable();
            $table->enum('status_pembayaran', ['pending', 'paid'])->default('pending');

            // STEP 7 - Berkas
            $table->string('file_ktp_nama')->nullable();
            $table->string('file_ktp_path')->nullable();

            $table->string('file_kk_nama')->nullable();
            $table->string('file_kk_path')->nullable();

            // rapor bisa multi file
            $table->string('file_rapor_nama')->nullable(); // opsional (ringkas)
            $table->json('file_rapor_paths')->nullable();  // simpan list path

            $table->boolean('berkas_terunggah')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pmb_yayasan_registrations');
    }
};

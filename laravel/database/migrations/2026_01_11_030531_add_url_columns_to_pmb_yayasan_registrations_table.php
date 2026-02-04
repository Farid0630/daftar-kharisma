<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pmb_yayasan_registrations', function (Blueprint $table) {

            // ===== Bukti Prestasi =====
            if (!Schema::hasColumn('pmb_yayasan_registrations', 'bukti_prestasi_url')) {
                $table->text('bukti_prestasi_url')->nullable()->after('bukti_prestasi_path');
            }

            // ===== KTP =====
            if (!Schema::hasColumn('pmb_yayasan_registrations', 'file_ktp_url')) {
                $table->text('file_ktp_url')->nullable()->after('file_ktp_path');
            }

            // ===== KK =====
            if (!Schema::hasColumn('pmb_yayasan_registrations', 'file_kk_url')) {
                $table->text('file_kk_url')->nullable()->after('file_kk_path');
            }

            // OPTIONAL: kalau Anda ingin simpan URL foto juga (controller Anda saat ini tidak pakai)
            // if (!Schema::hasColumn('pmb_yayasan_registrations', 'foto_url')) {
            //     $table->text('foto_url')->nullable()->after('foto_path');
            // }
        });
    }

    public function down(): void
    {
        Schema::table('pmb_yayasan_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('pmb_yayasan_registrations', 'bukti_prestasi_url')) {
                $table->dropColumn('bukti_prestasi_url');
            }
            if (Schema::hasColumn('pmb_yayasan_registrations', 'file_ktp_url')) {
                $table->dropColumn('file_ktp_url');
            }
            if (Schema::hasColumn('pmb_yayasan_registrations', 'file_kk_url')) {
                $table->dropColumn('file_kk_url');
            }

            // OPTIONAL
            // if (Schema::hasColumn('pmb_yayasan_registrations', 'foto_url')) {
            //     $table->dropColumn('foto_url');
            // }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ===== Titik sekolah + radius di pengaturan =====
        Schema::table('pengaturan', function (Blueprint $table) {
            if (!Schema::hasColumn('pengaturan', 'lat')) {
                $table->double('lat', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('pengaturan', 'lng')) {
                $table->double('lng', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('pengaturan', 'radius_meter')) {
                $table->unsignedInteger('radius_meter')->default(150);
            }
        });

        // ===== Audit lokasi absen di absensi_petugas =====
        Schema::table('absensi_petugas', function (Blueprint $table) {
            if (!Schema::hasColumn('absensi_petugas', 'absen_lat')) {
                $table->double('absen_lat', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('absensi_petugas', 'absen_lng')) {
                $table->double('absen_lng', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('absensi_petugas', 'jarak_meter')) {
                $table->unsignedInteger('jarak_meter')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan', function (Blueprint $table) {
            foreach (['lat', 'lng', 'radius_meter'] as $c) {
                if (Schema::hasColumn('pengaturan', $c)) $table->dropColumn($c);
            }
        });
        Schema::table('absensi_petugas', function (Blueprint $table) {
            foreach (['absen_lat', 'absen_lng', 'jarak_meter'] as $c) {
                if (Schema::hasColumn('absensi_petugas', $c)) $table->dropColumn($c);
            }
        });
    }
};
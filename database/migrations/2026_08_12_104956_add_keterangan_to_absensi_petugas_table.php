<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi_petugas', function (Blueprint $table) {
            if (!Schema::hasColumn('absensi_petugas', 'keterangan')) {
                $table->text('keterangan')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('absensi_petugas', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });
    }
};
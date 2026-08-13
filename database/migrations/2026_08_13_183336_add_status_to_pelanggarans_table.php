<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pelanggarans', function (Blueprint $table) {
            // Tambah semua kolom yang mungkin hilang di cloud (aman: ada guard)
            if (!Schema::hasColumn('pelanggarans', 'jenis_pelanggaran')) {
                $table->string('jenis_pelanggaran')->nullable();
            }
            if (!Schema::hasColumn('pelanggarans', 'keterangan')) {
                $table->text('keterangan')->nullable();
            }
            if (!Schema::hasColumn('pelanggarans', 'poin')) {
                $table->integer('poin')->nullable()->default(0);
            }
            if (!Schema::hasColumn('pelanggarans', 'foto_bukti')) {
                $table->string('foto_bukti')->nullable();
            }
            if (!Schema::hasColumn('pelanggarans', 'status')) {
                $table->string('status')->nullable()->default('diproses');
            }
            if (!Schema::hasColumn('pelanggarans', 'petugas_id')) {
                $table->unsignedBigInteger('petugas_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pelanggarans', function (Blueprint $table) {
            foreach (['jenis_pelanggaran', 'keterangan', 'poin', 'foto_bukti', 'status', 'petugas_id'] as $kolom) {
                if (Schema::hasColumn('pelanggarans', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
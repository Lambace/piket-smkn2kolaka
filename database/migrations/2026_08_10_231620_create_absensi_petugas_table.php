<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_petugas', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('nama');
            $table->string('jabatan')->default('Guru Piket');
            $table->time('jam_masuk')->nullable();
            $table->string('status')->default('tepat_waktu');
            $table->timestamps();
            $table->unique(['tanggal', 'nama']); // cegah absen ganda
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_petugas');
    }
};
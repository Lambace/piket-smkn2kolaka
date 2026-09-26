<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buku_tamu', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('telepon')->nullable();
            $table->string('instansi')->nullable();
            $table->string('keperluan');
            $table->string('bertemu_dengan')->nullable();
            $table->time('jam_masuk');
            $table->time('jam_keluar')->nullable();
            $table->date('tanggal_kunjungan');
            $table->text('catatan')->nullable();
            $table->string('foto_ktp')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buku_tamu');
    }
};
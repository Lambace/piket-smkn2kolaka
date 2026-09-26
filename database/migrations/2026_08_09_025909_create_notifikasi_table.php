<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
            $table->string('jenis')->default('whatsapp');
            $table->string('penerima_tipe');
            $table->unsignedBigInteger('penerima_id');
            $table->string('nomor_tujuan');
            $table->text('pesan');
            $table->string('status')->default('menunggu');
            $table->timestamp('terkirim_pada')->nullable();
            $table->text('pesan_error')->nullable();
            $table->timestamps();
            $table->index(['penerima_tipe', 'penerima_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
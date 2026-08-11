<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->string('nisn')->nullable()->change();
            $table->string('nis')->nullable()->change();
            $table->string('nama')->nullable()->change();
            $table->string('kelas')->nullable()->change();
            $table->string('jurusan')->nullable()->change();
            $table->string('jenis_kelamin')->nullable()->change();
            $table->text('alamat')->nullable()->change();
            $table->string('telepon')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Tidak perlu dikembalikan
    }
};
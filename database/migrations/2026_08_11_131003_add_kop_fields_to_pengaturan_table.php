<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $kolomBaru = ['kop_baris1', 'kop_baris2', 'alamat', 'telepon', 'email', 'website', 'server'];

        foreach ($kolomBaru as $kolom) {
            if (!Schema::hasColumn('pengaturan', $kolom)) {
                Schema::table('pengaturan', function (Blueprint $table) use ($kolom) {
                    $table->string($kolom)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('pengaturan', function (Blueprint $table) {
            foreach (['kop_baris1', 'kop_baris2', 'alamat', 'telepon', 'email', 'website', 'server'] as $kolom) {
                if (Schema::hasColumn('pengaturan', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
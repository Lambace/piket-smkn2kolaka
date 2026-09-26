<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('pengaturan', 'kop_nama_sekolah')) {
            Schema::table('pengaturan', function (Blueprint $table) {
                $table->string('kop_nama_sekolah')->nullable()->after('kop_baris2');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pengaturan', 'kop_nama_sekolah')) {
            Schema::table('pengaturan', function (Blueprint $table) {
                $table->dropColumn('kop_nama_sekolah');
            });
        }
    }
};
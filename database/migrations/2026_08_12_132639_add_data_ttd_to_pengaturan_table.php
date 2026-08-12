<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan', function (Blueprint $table) {
            if (!Schema::hasColumn('pengaturan', 'kepala_sekolah')) {
                $table->string('kepala_sekolah', 100)->nullable();
            }
            if (!Schema::hasColumn('pengaturan', 'nip_kepala_sekolah')) {
                $table->string('nip_kepala_sekolah', 30)->nullable();
            }
            if (!Schema::hasColumn('pengaturan', 'koordinator_piket')) {
                $table->string('koordinator_piket', 100)->nullable();
            }
            if (!Schema::hasColumn('pengaturan', 'nip_koordinator_piket')) {
                $table->string('nip_koordinator_piket', 30)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan', function (Blueprint $table) {
            $table->dropColumn([
                'kepala_sekolah', 'nip_kepala_sekolah',
                'koordinator_piket', 'nip_koordinator_piket',
            ]);
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom WA & token Fonnte per petugas (pengirim laporan harian)
        if (!Schema::hasColumn('users', 'no_wa')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('no_wa', 30)->nullable()->after('hari_piket');
                $table->string('fonnte_token', 120)->nullable()->after('no_wa');
            });
        }

        // ID grup WA sekolah (tujuan laporan)
        if (!Schema::hasColumn('pengaturan', 'wa_grup')) {
            Schema::table('pengaturan', function (Blueprint $table) {
                $table->string('wa_grup', 60)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['no_wa', 'fonnte_token']);
        });

        Schema::table('pengaturan', function (Blueprint $table) {
            $table->dropColumn('wa_grup');
        });
    }
};
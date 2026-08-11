<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $kolomBaru = [
            'nama_instansi'    => 'string',
            'logo_instansi'    => 'string',
            'kop_baris1'       => 'string',
            'kop_baris2'       => 'string',
            'kop_nama_sekolah' => 'string',
            'alamat'           => 'string',
            'telepon'          => 'string',
            'email'            => 'string',
            'website'          => 'string',
            'server'           => 'string',
        ];

        Schema::table('pengaturan', function (Blueprint $table) use ($kolomBaru) {
            foreach ($kolomBaru as $nama => $tipe) {
                if (!Schema::hasColumn('pengaturan', $nama)) {
                    $table->{$tipe}($nama)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan', function (Blueprint $table) {
            $table->dropColumn([
                'nama_instansi', 'logo_instansi',
                'kop_baris1', 'kop_baris2', 'kop_nama_sekolah',
                'alamat', 'telepon', 'email', 'website', 'server',
            ]);
        });
    }
};
<?php

namespace App\Console\Commands;

use App\Models\AbsensiPetugas;
use App\Models\User;
use Illuminate\Console\Command;

class AutoHadirPetugas extends Command
{
    protected $signature = 'piket:auto-hadir';
    protected $description = 'Absen hadir otomatis untuk petugas bertanda auto_hadir';

    public function handle()
    {
        $today = now()->toDateString();

        foreach (User::where('auto_hadir', true)->get() as $u) {
            $sudah = AbsensiPetugas::where('tanggal', $today)
                ->where('nama', $u->name)->exists();

            if ($sudah) {
                $this->line("Lewati (sudah absen): {$u->name}");
                continue;
            }

            // ===== JAM ACAK 06:45:00 – 06:59:59 =====
            $jamMasuk = sprintf('06:%02d:%02d', rand(45, 59), rand(0, 59));

            AbsensiPetugas::create([
                'nama'       => $u->name,
                'jabatan'    => $u->role === 'koordinator' ? 'Koordinator Piket' : 'Guru Piket',
                'tanggal'    => $today,
                'jam_masuk'  => $jamMasuk,
                'status'     => 'tepat_waktu',
                'keterangan' => 'Hadir otomatis (default hadir)',
            ]);

            $this->info("Auto-hadir tercatat: {$u->name} ({$jamMasuk})");
        }

        return 0;
    }
}
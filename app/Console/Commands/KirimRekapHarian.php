<?php

namespace App\Console\Commands;

use App\Services\RekapHarianService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class KirimRekapHarian extends Command
{
    protected $signature = 'rekap:kirim-harian {--tanggal= : Format YYYY-MM-DD, default hari ini}';

    protected $description = 'Kirim rekap harian via WA ke semua wali kelas aktif';

    public function handle(RekapHarianService $service): int
    {
        $tanggal = $this->option('tanggal') ? Carbon::parse($this->option('tanggal')) : null;

        $hasil = $service->kirimSemua($tanggal) ?? [];

        $dikirim  = $hasil['dikirim']  ?? 0;
        $dilewati = $hasil['dilewati'] ?? 0;
        $gagal    = $hasil['gagal']    ?? 0;

        $this->info("✅ Dikirim: {$dikirim} | ⏭ Dilewati (sudah kirim): {$dilewati} | ❌ Gagal: {$gagal}");

        return Command::SUCCESS;
    }
}
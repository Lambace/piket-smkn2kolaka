<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BersihkanLaporanPdf extends Command
{
    protected $signature = 'laporan:bersih-pdf';
    protected $description = 'Hapus file PDF laporan yang berusia lebih dari 2 hari';

    public function handle(): int
    {
        $files = Storage::disk('public')->files('laporan');
        $deleted = 0;

        foreach ($files as $file) {
            $umur = now()->timestamp - Storage::disk('public')->lastModified($file);

            // Hapus jika lebih dari 2 hari (172800 detik)
            if ($umur > 172800) {
                Storage::disk('public')->delete($file);
                $deleted++;
            }
        }

        $this->info("🧹 {$deleted} file PDF lama dihapus dari storage.");
        return Command::SUCCESS;
    }
}
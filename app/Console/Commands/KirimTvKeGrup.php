<?php

namespace App\Console\Commands;

use App\Models\Pengaturan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class KirimTvKeGrup extends Command
{
    protected $signature = 'tv:kirim-grup {--grup= : ID grup WA (default: env WA_GROUP_ID)}';

    protected $description = 'Kirim screenshot halaman live TV + laporan harian ke grup WhatsApp';

    public function handle(): int
    {
        $token = env('FONNTE_TOKEN');
        $grup  = $this->option('grup') ?? env('WA_GROUP_ID');

        if (empty($token)) {
            $this->error('FONNTE_TOKEN belum diisi di environment.');
            return Command::FAILURE;
        }

        if (empty($grup)) {
            $this->error('WA_GROUP_ID belum diisi (contoh: 628xxx@g.us).');
            return Command::FAILURE;
        }

        $key    = env('DISPLAY_KEY', 'piket2026');
        $urlTv  = url('/tampil') . '?k=' . $key;
        $urlPdf = url('/tampil/laporan') . '?' . http_build_query([
            'jenis'   => 'gabungan',
            'periode' => 'harian',               // ← HARIAN saja
            'tanggal' => now()->toDateString(),  // ← tanggal hari ini
            'k'       => $key,
        ]);

        // Screenshot halaman TV (layanan gratis thum.io)
        $screenshot = 'https://image.thum.io/get/width/1280/crop/720/' . $urlTv;

        $sekolah = Pengaturan::first()?->nama_sekolah ?? 'SMKN 2 KOLAKA';
        $now     = now()->locale('id');

        $caption = implode("\n", [
            "📺 PIKET {$sekolah} — {$now->isoFormat('dddd, D MMMM Y')}",
            "⏰ Screenshot Pukul {$now->isoFormat('HH.mm')} WITA",
            '',
            'Papan Informasi Piket hari ini:',
            '',
            '🔴 Lihat Halaman Live',
            $urlTv,
            '',
            '📄 Lihat Laporan (Hari Ini)',
            $urlPdf,
            '',
            '© Sistem Informasi Piket',
        ]);

        $res = Http::asForm()
            ->withToken($token)
            ->timeout(60)
            ->post('https://fonnte.com/send', [
                'to'      => $grup,
                'message' => $caption,
                'media'   => $screenshot,
            ]);

        $ok = $res->successful() && ($res->json('status') ?? false);

        if ($ok) {
            $this->info("✅ Screenshot TV + laporan harian terkirim ke {$grup}");
            return Command::SUCCESS;
        }

        $this->error('❌ Gagal kirim: ' . ($res->json('reason') ?? $res->body()));
        return Command::FAILURE;
    }
}
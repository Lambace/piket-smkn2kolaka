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
        $grup = $this->option('grup') ?? env('WA_GROUP_ID');

        if (empty($grup)) {
            $this->error('WA_GROUP_ID belum diisi (contoh: 628xxx@g.us).');
            return Command::FAILURE;
        }

        // ===== Token Fonnte: dari Pengaturan (seperti fitur WA lain), fallback env =====
        $pengaturan = Pengaturan::first();

        $token = env('FONNTE_TOKEN');
        if (empty($token) && $pengaturan) {
            foreach ($pengaturan->getAttributes() as $kolom => $nilai) {
                if ((str_contains($kolom, 'fonnte') || str_contains($kolom, 'token')) && !empty($nilai)) {
                    $token = $nilai;
                    break;
                }
            }
        }

        if (empty($token)) {
            $this->error('Token Fonnte tidak ditemukan. Isi di menu Pengaturan atau env FONNTE_TOKEN.');
            return Command::FAILURE;
        }

        $key    = env('DISPLAY_KEY', 'piket2026');
        $urlTv  = url('/tampil') . '?k=' . $key;
        $urlPdf = url('/tampil/laporan') . '?' . http_build_query([
            'jenis'   => 'gabungan',
            'periode' => 'harian',
            'tanggal' => now()->toDateString(),
            'k'       => $key,
        ]);

        // Screenshot halaman TV (layanan gratis thum.io)
        $screenshot = 'https://image.thum.io/get/width/1280/crop/720/' . $urlTv;

        $sekolah = $pengaturan?->nama_sekolah ?? 'SMKN 2 KOLAKA';
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
<?php

namespace App\Console\Commands;

use App\Models\Pengaturan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class KirimTvKeGrup extends Command
{
    protected $signature = 'tv:kirim-grup {--grup= : ID grup WA (default: env WA_GROUP_ID)}';

    protected $description = 'Kirim banner Laporan Tim Piket ke grup WhatsApp';

    public function handle(): int
    {
        $grup = $this->option('grup') ?? env('WA_GROUP_ID');

        if (empty($grup)) {
            $this->error('WA_GROUP_ID belum diisi (contoh: 628xxx@g.us).');
            return Command::FAILURE;
        }

        // ===== Token Fonnte =====
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

        // ===== Logo sekolah via route publik =====
        $logoUrl = ($pengaturan?->logo) ? route('logo.sekolah') : null;
        $this->info('Logo URL: ' . ($logoUrl ?? '(logo belum di-upload)'));

        $key    = env('DISPLAY_KEY', 'piket2026');
        $urlTv  = url('/tampil') . '?k=' . $key;
        $urlPdf = url('/tampil/laporan') . '?' . http_build_query([
            'jenis'   => 'gabungan',
            'periode' => 'harian',
            'tanggal' => now()->toDateString(),
            'k'       => $key,
        ]);

        $sekolah = $pengaturan?->nama_sekolah ?? 'SMKN 2 KOLAKA';
        $now     = now()->locale('id');
        $hari    = strtoupper($now->isoFormat('dddd'));

        // ===== CAPTION (Live View + Download Laporan) =====
        $caption = implode("\n", [
            '*LAPORAN TIM PIKET ' . $hari . '*',
            '_' . $sekolah . '_',
            $now->isoFormat('dddd, D MMMM Y'),
            '',
            '🔴 *Live View* — lihat dashboard piket hari ini:',
            $urlTv,
            '',
            '📄 *Download Laporan* — unduh PDF laporan harian:',
            $urlPdf,
        ]);

        // ===== API Fonnte =====
        $payload = [
            'target'  => $grup,
            'message' => $caption,
        ];

        if ($logoUrl) {
            $payload['url'] = $logoUrl;
        }

        $res = Http::withHeaders(['Authorization' => $token])
            ->timeout(60)
            ->asForm()
            ->post('https://api.fonnte.com/send', $payload);

        $body = [];
        if ($res->successful()) {
            try {
                $body = $res->json() ?? [];
            } catch (\Throwable $e) {
                $body = ['status' => false, 'reason' => 'Response bukan JSON'];
            }
        }

        $ok = $res->successful() && ($body['status'] ?? false);

        if ($ok) {
            $this->info("✅ Banner Tim Piket terkirim ke {$grup}");
            return Command::SUCCESS;
        }

        $this->error('❌ Gagal kirim: ' . ($body['reason'] ?? ($res->successful() ? 'Status false' : 'HTTP ' . $res->status())));
        return Command::FAILURE;
    }
}
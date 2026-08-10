<?php

namespace App\Console\Commands;

use App\Models\AbsensiPetugas;
use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class KirimTvKeGrup extends Command
{
    protected $signature = 'tv:kirim-grup {--grup= : ID grup WA (default: env WA_GROUP_ID)}';

    protected $description = 'Kirim laporan tim piket ke grup WhatsApp';

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

        // ===== Logo sekolah via route publik (bisa diunduh Fonnte) =====
        $logoUrl = ($pengaturan?->logo) ? route('logo.public') : null;
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

        // ===== Statistik kehadiran petugas =====
        $jumlahHadir   = AbsensiPetugas::where('tanggal', now()->toDateString())->count();
        $jumlahPetugas = User::where('role', 'petugas')->count();
        $jumlahAlpha   = max(0, $jumlahPetugas - $jumlahHadir);

        // ===== CAPTION (tanpa separator atas, tanpa emoji 📺) =====
        $caption = implode("\n", [
            '      *LAPORAN TIM PIKET*',
            '           *' . $hari . '*',
            '━━━━━━━━━━━━━━━━━━━━━━━',
            '     _' . $sekolah . '_',
            '',
            '   _' . $now->isoFormat('dddd, D MMMM Y') . '_',
            '   _Pukul ' . $now->isoFormat('HH.mm') . ' WITA_',
            '',
            '━━━━━━━━━━━━━━━━━━━━━━━',
            '   👥 Petugas Hadir : *' . $jumlahHadir . ' orang*',
            '   ❌ Alpha             : *' . $jumlahAlpha . ' orang*',
            '━━━━━━━━━━━━━━━━━━━━━━━',
            '',
            '*🔴 LIHAT HALAMAN LIVE*',
            $urlTv,
            '',
            '*📄 DOWNLOAD LAPORAN PDF*',
            $urlPdf,
            '',
            '━━━━━━━━━━━━━━━━━━━━━━━',
            '      _© Sistem Informasi Piket_',
            '━━━━━━━━━━━━━━━━━━━━━━━',
        ]);

        // ===== API Fonnte (logo sekolah sebagai gambar + caption) =====
        $payload = [
            'target'  => $grup,
            'message' => $caption,
        ];

        // Jika logo ada, kirim sebagai gambar
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
            $this->info("✅ Laporan Tim Piket terkirim ke {$grup}");
            return Command::SUCCESS;
        }

        $this->error('❌ Gagal kirim: ' . ($body['reason'] ?? ($res->successful() ? 'Status false' : 'HTTP ' . $res->status())));
        return Command::FAILURE;
    }
}
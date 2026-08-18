<?php

namespace App\Console\Commands;

use App\Models\Pengaturan;
// Tambahkan import untuk File dan Intervention Image
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class KirimTvKeGrup extends Command
{
    protected $signature = 'tv:kirim-grup {--grup= : ID grup WA (default: env WA_GROUP_ID)}';

    protected $description = 'Kirim banner Laporan Tim Piket profesional ke grup WhatsApp';

    public function handle(): int
    {
        $grup = $this->option('grup') ?? env('WA_GROUP_ID');

        if (empty($grup)) {
            $this->error('❌ WA_GROUP_ID belum diisi (contoh: 628xxx@g.us).');
            return Command::FAILURE;
        }

        // ===== 1. Token Fonnte =====
        $token = env('FONNTE_TOKEN');
        
        // Fallback ke database jika tidak ada di .env (menggunakan logika asli Anda)
        if (empty($token)) {
            $pengaturan = Pengaturan::first();
            if ($pengaturan) {
                foreach ($pengaturan->getAttributes() as $kolom => $nilai) {
                    if ((str_contains($kolom, 'fonnte') || str_contains($kolom, 'token')) && !empty($nilai)) {
                        $token = $nilai;
                        break;
                    }
                }
            }
        }

        if (empty($token)) {
            $this->error('❌ Token Fonnte tidak ditemukan. Isi di menu Pengaturan atau env FONNTE_TOKEN.');
            return Command::FAILURE;
        }

        // ===== 2. Ambil Data =====
        $pengaturan = Pengaturan::first();
        $sekolah = $pengaturan?->nama_sekolah ?? 'SMKN 2 KOLAKA';
        $now = now()->locale('id');
        $hari = $now->isoFormat('dddd'); // Contoh: Sabtu
        $tanggal = $now->isoFormat('dddd, D MMMM Y'); // Contoh: Sabtu, 18 Agustus 2026

        // ⚠️ PENTING: Ganti angka di bawah ini dengan Query Database asli Anda!
        // Contoh: $petugasHadir = AbsensiPiket::where('tanggal', $now->toDateString())->where('status', 'hadir')->count();
        $petugasHadir = 12; 
        $alpha = 0; 

        $key = env('DISPLAY_KEY', 'piket2026');
        $urlTv = url('/tampil') . '?k=' . $key;
        $urlPdf = url('/tampil/laporan') . '?' . http_build_query([
            'jenis'   => 'gabungan',
            'periode' => 'harian',
            'tanggal' => $now->toDateString(),
            'k'       => $key,
        ]);

        // ===== 3. Generate Banner Profesional =====
        $this->info('🎨 Sedang membuat banner...');
        
        $templatePath = public_path('images/banner-bg.png');
        if (!File::exists($templatePath)) {
            $this->error('❌ File template banner-bg.png tidak ditemukan di public/images/');
            return Command::FAILURE;
        }

        $manager = new ImageManager(new Driver());
        $image = $manager->read($templatePath);

        // A. Overlay Logo Dinamis (Menimpa lingkaran putih)
        if ($pengaturan?->logo) {
            $logoPath = public_path('storage/' . $pengaturan->logo);
            if (File::exists($logoPath)) {
                $logo = $manager->read($logoPath);
                $logo->resize(180, 180); // Ukuran logo
                $image->place($logo, 'center', 0, -550); // Geser ke atas agar pas di lingkaran
                $this->info('✅ Logo dinamis ditambahkan.');
            }
        }

        // B. Overlay Tanggal (Kotak Putih)
        $image->text($tanggal, 540, 750, function($font) {
            $font->size(40);
            $font->color('#2c3e50');
            $font->align('center');
            $font->valign('middle');
        });

        // C. Overlay Angka Hadir (Kotak Hijau)
        $image->text((string)$petugasHadir, 340, 1300, function($font) {
            $font->size(100);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });
        // Label Hadir
        $image->text('HADIR', 340, 1200, function($font) {
            $font->size(22);
            $font->color('#ffffff');
            $font->align('center');
        });

        // D. Overlay Angka Alpha (Kotak Merah)
        $image->text((string)$alpha, 740, 1300, function($font) {
            $font->size(100);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });
        // Label Alpha
        $image->text('ALPHA', 740, 1200, function($font) {
            $font->size(22);
            $font->color('#ffffff');
            $font->align('center');
        });

        // Simpan banner sementara di folder public/banners
        $folder = public_path('banners');
        if (!File::isDirectory($folder)) {
            File::makeDirectory($folder, 0755, true);
        }

        $fileName = 'piket-' . now()->timestamp . '.png';
        $savePath = $folder . '/' . $fileName;
        $image->save($savePath);
        
        $bannerUrl = url('banners/' . $fileName);
        $this->info('✅ Banner berhasil dibuat: ' . $bannerUrl);

        // ===== 4. Siapkan Caption (Opsi 1: Link di Caption) =====
        $caption = implode("\n", [
            '*LAPORAN TIM PIKET ' . strtoupper($hari) . '*',
            '_' . $sekolah . '_',
            $tanggal,
            '',
            '👥 Petugas Hadir: *' . $petugasHadir . ' orang*',
            '🔴 Alpha: *' . $alpha . ' orang*',
            '',
            '🔴 *Live View* — lihat dashboard piket hari ini:',
            $urlTv,
            '',
            '📄 *Download Laporan* — unduh PDF laporan harian:',
            $urlPdf,
            '',
            '_© Sistem Informasi Piket - Si Piket_',
        ]);

        // ===== 5. Kirim ke API Fonnte =====
        $payload = [
            'target'  => $grup,
            'message' => $caption,
            'url'     => $bannerUrl, // Mengirim gambar banner yang baru digenerate
        ];

        $res = Http::withHeaders(['Authorization' => $token])
            ->timeout(60)
            ->asForm()
            ->post('https://api.fonnte.com/send', $payload);

        // ===== 6. Hapus File Sementara (Agar server tidak penuh) =====
        File::delete($savePath);
        $this->info('🗑️ File banner sementara dihapus.');

        // ===== 7. Evaluasi Response =====
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
            $this->info("✅ Banner Tim Piket berhasil terkirim ke {$grup}");
            return Command::SUCCESS;
        }

        $this->error('❌ Gagal kirim: ' . ($body['reason'] ?? ($res->successful() ? 'Status false' : 'HTTP ' . $res->status())));
        return Command::FAILURE;
    }
}
<?php

namespace App\Console\Commands;

use App\Models\Pengaturan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManagerStatic as Image;

class KirimTvKeGrup extends Command
{
    protected $signature = 'tv:kirim-grup {--grup= : ID grup WA (default: env WA_GROUP_ID)}';
    protected $description = 'Kirim banner Laporan Tim Piket profesional ke grup WhatsApp';

    public function handle(): int
    {
        $grup = $this->option('grup') ?? env('WA_GROUP_ID');

        if (empty($grup)) {
            $this->error('❌ WA_GROUP_ID belum diisi.');
            return Command::FAILURE;
        }

        // ===== 1. Token Fonnte =====
        $token = env('FONNTE_TOKEN');
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
            $this->error('❌ Token Fonnte tidak ditemukan.');
            return Command::FAILURE;
        }

        // ===== 2. Ambil Data =====
        $pengaturan = Pengaturan::first();
        $sekolah = $pengaturan?->nama_sekolah ?? 'SMKN 2 KOLAKA';
        $now = now()->locale('id');
        $hari = $now->isoFormat('dddd');
        $tanggal = $now->isoFormat('dddd, D MMMM Y');

        // ⚠️ GANTI dengan query database asli Anda!
        $petugasHadir = 0; 
        $alpha = 0; 

        $key = env('DISPLAY_KEY', 'piket2026');
        $urlTv = url('/tampil') . '?k=' . $key;
        $urlPdf = url('/tampil/laporan') . '?' . http_build_query([
            'jenis'   => 'gabungan',
            'periode' => 'harian',
            'tanggal' => $now->toDateString(),
            'k'       => $key,
        ]);

        // ===== 3. Generate Banner =====
        $this->info('🎨 Sedang membuat banner...');
        
        $templatePath = public_path('images/banner-bg.png');
        if (!File::exists($templatePath)) {
            $this->error('❌ File template banner-bg.png tidak ditemukan di public/images/');
            return Command::FAILURE;
        }

        $image = Image::make($templatePath);

        // A. Overlay Logo Dinamis
        if ($pengaturan?->logo) {
            $logoPath = public_path('storage/' . $pengaturan->logo);
            if (File::exists($logoPath)) {
                $logo = Image::make($logoPath)->resize(180, 180, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $image->insert($logo, 'top', 0, 50);
                $this->info('✅ Logo dinamis ditambahkan.');
            }
        }

        // B. Overlay Tanggal
        $image->text($tanggal, 540, 750, function($font) {
            $font->size(40);
            $font->color('#2c3e50');
            $font->align('center');
            $font->valign('middle');
        });

        // C. Overlay Angka Hadir
        $image->text((string)$petugasHadir, 340, 1300, function($font) {
            $font->size(100);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });
        $image->text('HADIR', 340, 1200, function($font) {
            $font->size(22);
            $font->color('#ffffff');
            $font->align('center');
        });

        // D. Overlay Angka Alpha
        $image->text((string)$alpha, 740, 1300, function($font) {
            $font->size(100);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });
        $image->text('ALPHA', 740, 1200, function($font) {
            $font->size(22);
            $font->color('#ffffff');
            $font->align('center');
        });

        // Simpan di folder PUBLIC agar URL langsung bisa diakses tanpa symlink storage
        $folder = public_path('banners');
        if (!File::isDirectory($folder)) {
            File::makeDirectory($folder, 0755, true);
        }

        $fileName = 'piket-' . now()->timestamp . '.png';
        $savePath = $folder . '/' . $fileName;
        $image->save($savePath);
        
        // URL Publik langsung dari folder public
        $bannerUrl = url('banners/' . $fileName);
        $this->info('✅ Banner berhasil disimpan: ' . $bannerUrl);

        // ===== 4. Siapkan Caption =====
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

        // ===== 5. Kirim ke Fonnte =====
        $this->info('📱 Mengirim ke Fonnte...');
        
        $payload = [
            'target'  => $grup,
            'message' => $caption,
            'url'     => $bannerUrl,
        ];

        $res = Http::withHeaders(['Authorization' => $token])
            ->timeout(120)
            ->asForm()
            ->post('https://api.fonnte.com/send', $payload);

        // ===== 6. Evaluasi =====
        $body = [];
        if ($res->successful()) {
            try {
                $body = $res->json() ?? [];
                $this->info('📱 Response Fonnte: ' . json_encode($body));
            } catch (\Throwable $e) {
                $body = ['status' => false, 'reason' => 'Response bukan JSON'];
            }
        } else {
            $this->error('❌ HTTP Error: ' . $res->status());
            $this->error('Response: ' . $res->body());
        }

        // ===== 7. JANGAN HAPUS FILE SEKARANG =====
        // Fonnte memproses pesan secara "pending" (antrean). 
        // Jika file dihapus sekarang, Fonnte akan gagal mendownload gambar (404).
        // File akan menumpuk sedikit di folder public/banners (hanya ~100KB per hari), 
        // Anda bisa membersihkannya manual per bulan jika perlu.
        $this->info('💾 File banner disimpan (tidak dihapus agar Fonnte bisa memproses antrean).');

        $ok = $res->successful() && ($body['status'] ?? false);

        if ($ok) {
            $this->info("✅ Banner berhasil terkirim ke {$grup}");
            return Command::SUCCESS;
        }

        $this->error('❌ Gagal kirim: ' . ($body['reason'] ?? 'Unknown error'));
        return Command::FAILURE;
    }
}
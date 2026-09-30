<?php

namespace App\Console\Commands;

use App\Models\AbsensiPetugas;
use App\Models\Pengaturan;
use App\Models\User;
use App\Services\PengirimWaResolver;
use App\Services\WaRelayService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManagerStatic as Image;

class KirimTvKeGrup extends Command
{
    protected $signature = 'tv:kirim-grup {--hari= : Simulasi hari untuk uji coba, contoh: --hari=Rabu}';
    protected $description = 'Kirim banner Laporan Tim Piket ke grup sekolah (otomatis harian)';

    public function handle(PengirimWaResolver $resolver, WaRelayService $relay): int
    {
        $now       = Carbon::now('Asia/Makassar');
        $hariIni   = $this->option('hari') ?: $now->isoFormat('dddd');
        $tanggal   = $now->isoFormat('dddd, D MMMM Y');
        $todayStr  = $now->toDateString();
        $isSimulasi = !empty($this->option('hari'));

        // ===== PENGAMAN SIMULASI: konfirmasi sebelum kirim sungguhan =====
        if ($isSimulasi && !$this->confirm("⚠️  SIMULASI hari {$hariIni}? Pesan SUNGGUHAN akan terkirim ke WA. Lanjutkan?")) {
            $this->info('Dibatalkan.');
            return Command::SUCCESS;
        }

        // ===== GATEKEEPER 1: Libur Minggu =====
        if ($hariIni === 'Minggu') {
            $this->info("🗓️ Hari Minggu — libur otomatis, skip pengiriman.");
            return Command::SUCCESS;
        }

        // ===== GATEKEEPER 2: Koordinator aktif di HARI SASARAN =====
        // FIX 1: resolver menerima $hariIni agar simulasi bekerja
        $koordinator = $resolver->resolve($hariIni);
        if (!$koordinator) {
            $this->warn("⚠️ Tidak ada koordinator aktif untuk hari {$hariIni}. Skip pengiriman.");
            return Command::SUCCESS;
        }

        $this->info("✅ Hari {$hariIni}" . ($isSimulasi ? " (SIMULASI)" : "") . " — koordinator aktif: {$koordinator->name}");

        // ===== AMBIL DATA REAL DARI DATABASE =====
        $pengaturan = Pengaturan::first();
        $sekolah    = $pengaturan?->nama_sekolah ?? 'SMKN 2 KOLAKA';

        // Petugas yang JADWAL piket pada HARI SASARAN (mendukung simulasi)
        $petugasJadwal = User::whereIn('role', ['petugas', 'koordinator'])
            ->where('hari_piket', $hariIni)
            ->get();

        // Absensi tercatat hari ini (tetap real, karena ini data historis aktual)
        $absensiHariIni = AbsensiPetugas::whereDate('tanggal', $todayStr)->get();

        $petugasHadir = 0;
        $alpha        = 0;
        foreach ($petugasJadwal as $u) {
            $namaKey = strtolower(trim($u->name));
            $record  = $absensiHariIni->first(
                fn($a) => strtolower(trim($a->nama)) === $namaKey
            );
            if ($record && in_array($record->status, ['tepat_waktu', 'terlambat'])) {
                $petugasHadir++;
            } else {
                $alpha++;
            }
        }

        // ===== GENERATE BANNER =====
        $this->info('🎨 Sedang membuat banner...');

        $templatePath = public_path('images/banner-bg.png');
        if (!File::exists($templatePath)) {
            $this->error('❌ File template banner-bg.png tidak ditemukan di public/images/');
            return Command::FAILURE;
        }

        $image = Image::make($templatePath);

        if ($pengaturan?->logo) {
            $logoPath = public_path('storage/' . $pengaturan->logo);
            if (File::exists($logoPath)) {
                $logo = Image::make($logoPath)->resize(180, 180, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $image->insert($logo, 'top', 0, 50);
            }
        }

        $image->text($tanggal, 540, 750, function($font) {
            $font->size(40);
            $font->color('#2c3e50');
            $font->align('center');
            $font->valign('middle');
        });

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

        $folder = public_path('banners');
        if (!File::isDirectory($folder)) {
            File::makeDirectory($folder, 0755, true);
        }

        $fileName  = 'piket-' . $now->timestamp . '.png';
        $savePath  = $folder . '/' . $fileName;
        $image->save($savePath);
        $bannerUrl = url('banners/' . $fileName);
        $this->info('✅ Banner disimpan: ' . $bannerUrl);

        // ===== SIAPKAN CAPTION =====
        $key    = env('DISPLAY_KEY', 'piket2026');
        $urlTv  = url('/tampil') . '?k=' . $key;
        $urlPdf = url('/tampil/laporan') . '?' . http_build_query([
            'jenis'   => 'gabungan',
            'periode' => 'harian',
            'tanggal' => $todayStr,
            'k'       => $key,
        ]);

        $caption = implode("\n", [
            '*LAPORAN TIM PIKET ' . strtoupper($hariIni) . '*',
            '_' . $sekolah . '_',
            $tanggal,
            '',
            '👥 Petugas Hadir: *' . $petugasHadir . ' orang*',
            '🔴 Alpha: *' . $alpha . ' orang*',
            '',
            '🔴 *Live View* — dashboard piket hari ini:',
            $urlTv,
            '',
            '📄 *Download Laporan* — PDF laporan harian:',
            $urlPdf,
            '',
            '_© Sistem Informasi Si_Piket_',
        ]);

        // ===== KIRIM LEWAT WaRelayService =====
        $this->info('📱 Mengirim ke grup sekolah via WaRelayService...');
        // FIX 2: teruskan $hariIni agar service memilih koordinator yang tepat
        $ok = $relay->kirimBanner($bannerUrl, $caption, $hariIni);

        $this->newLine();
        $this->info('💾 Banner disimpan di public/banners/ (tidak dihapus untuk antrean Fonnte).');

        return $ok ? Command::SUCCESS : Command::FAILURE;
    }
}
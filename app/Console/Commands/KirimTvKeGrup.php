<?php

namespace App\Console\Commands;

use App\Models\AbsensiPetugas;
use App\Models\Pengaturan;
use App\Models\User;
use App\Services\PengirimWaResolver;
use App\Services\WaRelayService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class KirimTvKeGrup extends Command
{
    protected $signature = 'tv:kirim-grup {--hari= : Simulasi hari untuk uji coba, contoh: --hari=Rabu}';
    protected $description = 'Kirim banner Laporan Tim Piket ke grup sekolah (otomatis harian)';

    public function handle(PengirimWaResolver $resolver, WaRelayService $relay): int
    {
        $now        = Carbon::now('Asia/Makassar');
        $hariIni    = $this->option('hari') ?: $now->isoFormat('dddd');
        $tanggal    = $now->isoFormat('dddd, D MMMM Y');
        $todayStr   = $now->toDateString();
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
        $koordinator = $resolver->resolve($hariIni);
        if (!$koordinator) {
            $this->warn("⚠️ Tidak ada koordinator aktif untuk hari {$hariIni}. Skip pengiriman.");
            return Command::SUCCESS;
        }

        $this->info("✅ Hari {$hariIni}" . ($isSimulasi ? " (SIMULASI)" : "") . " — koordinator aktif: {$koordinator->name}");

        // ===== AMBIL DATA REAL DARI DATABASE (untuk caption) =====
        $pengaturan = Pengaturan::first();
        $sekolah    = $pengaturan?->nama_sekolah ?? 'SMKN 2 KOLAKA';

        $petugasJadwal = User::whereIn('role', ['petugas', 'koordinator'])
            ->where('hari_piket', $hariIni)
            ->get();

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

        // ===== BANNER ON-DEMAND: dirender via route, tanpa file tersimpan =====
        // Ini memecahkan masalah 404 di Laravel Cloud karena gambar dibuat saat diakses
        $bannerUrl = url('/banner/piket.png') . '?' . http_build_query([
            'hari'    => $hariIni,
            'tanggal' => $todayStr,
        ]);
        $this->info('✅ Banner on-demand siap: ' . $bannerUrl);

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
        $ok = $relay->kirimBanner($bannerUrl, $caption, $hariIni);

        $this->newLine();
        $this->info('💾 Banner akan di-render on-demand oleh server (tidak ada file tersimpan).');

        return $ok ? Command::SUCCESS : Command::FAILURE;
    }
}
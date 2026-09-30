<?php

namespace App\Console\Commands;

use App\Models\AbsensiPetugas;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoHadirPetugas extends Command
{
    protected $signature = 'piket:auto-hadir';
    protected $description = 'Absen hadir otomatis untuk petugas dengan auto_hadir=true di hari piket mereka (07:00-07:29 WITA)';

    protected const BATAS_TEPAT_WAKTU = '07:30:00';
    protected const MENIT_AWAL = 0;
    protected const MENIT_AKHIR = 29; // Selalu tepat waktu (07:00-07:29)

    public function handle()
    {
        $now = Carbon::now('Asia/Makassar');
        $todayStr = $now->toDateString();
        $namaHariIni = $now->isoFormat('dddd'); // "Senin", "Selasa", dst.

        // ===== AMBIL TARGET DINAMIS =====
        // Petugas yang: auto_hadir = true DAN hari_piket = hari ini
        $targetPetugas = User::whereIn('role', ['petugas', 'koordinator'])
            ->where('auto_hadir', true)
            ->where('hari_piket', $namaHariIni)
            ->get();

        if ($targetPetugas->isEmpty()) {
            $this->line("Tidak ada petugas auto-hadir untuk hari {$namaHariIni}");
            Log::info("[AUTO-HADIR] Lewati: tidak ada target untuk {$namaHariIni}");
            return 0;
        }

        $berhasil = 0;
        $dilewati = 0;

        foreach ($targetPetugas as $user) {
            // Cek apakah sudah absen hari ini
            $sudah = AbsensiPetugas::whereDate('tanggal', $todayStr)
                ->where('nama', $user->name)
                ->exists();

            if ($sudah) {
                $this->line("⏭️  Lewati (sudah absen): {$user->name}");
                $dilewati++;
                continue;
            }

            // ===== JAM ACAK 07:00:00 – 07:29:59 (selalu tepat waktu) =====
            $menit = rand(self::MENIT_AWAL, self::MENIT_AKHIR);
            $detik = rand(0, 59);
            $jamMasuk = sprintf('07:%02d:%02d', $menit, $detik);

            // ===== STATUS OTOMATIS =====
            $status = ($jamMasuk <= self::BATAS_TEPAT_WAKTU) ? 'tepat_waktu' : 'terlambat';

            AbsensiPetugas::create([
                'nama'       => $user->name,
                'jabatan'    => $user->role === 'koordinator' ? 'Koordinator Piket' : 'Guru Piket',
                'tanggal'    => $todayStr,
                'jam_masuk'  => $jamMasuk,
                'status'     => $status,
                'keterangan' => $status === 'terlambat' ? 'Auto-hadir (terlambat)' : null,
            ]);

            $this->info("✅ {$user->name} | {$jamMasuk} WITA | " . strtoupper($status));
            Log::info("[AUTO-HADIR] {$user->name} tercatat {$status} pada {$jamMasuk}");
            $berhasil++;
        }

        $this->newLine();
        $this->info("📊 Ringkasan {$namaHariIni}: {$berhasil} berhasil, {$dilewati} dilewati");
        return 0;
    }
}
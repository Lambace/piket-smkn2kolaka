<?php

namespace App\Console\Commands;

use App\Models\AbsensiPetugas;
use App\Models\User;
use Illuminate\Console\Command;
use Carbon\Carbon;

class AutoHadirPetugas extends Command
{
    protected $signature = 'piket:auto-hadir';
    protected $description = 'Absen hadir otomatis untuk petugas tertentu dengan waktu acak 07:00 - 07:48';

    public function handle()
    {
        // Gunakan timezone WITA agar tanggalnya sesuai
        $today = Carbon::now('Asia/Makassar')->toDateString();

        // Target spesifik: Muhammad Jasmin, S.Pd 
        // (Anda bisa menambahkan nama lain di array ini jika perlu)
        $targetPetugas = ['Muhammad Jasmin, S.Pd'];

        foreach ($targetPetugas as $namaTarget) {
            $user = User::where('name', $namaTarget)->first();
            
            if (!$user) {
                $this->warn("User tidak ditemukan: {$namaTarget}");
                continue;
            }

            // Cek apakah sudah absen hari ini
            $sudah = AbsensiPetugas::whereDate('tanggal', $today)
                ->where('nama', $user->name)->exists();

            if ($sudah) {
                $this->line("Lewati (sudah absen): {$user->name}");
                continue;
            }

            // ===== JAM ACAK 07:00:00 – 07:48:59 =====
            $menit = rand(0, 29);
            $detik = rand(0, 59);
            $jamMasuk = sprintf('07:%02d:%02d', $menit, $detik);

            // Simpan ke database lokal Anda
            AbsensiPetugas::create([
                'nama'       => $user->name,
                'jabatan'    => $user->role === 'koordinator' ? 'Koordinator Piket' : 'Guru Piket',
                'tanggal'    => $today,
                'jam_masuk'  => $jamMasuk,
                'status'     => 'tepat_waktu',
                'keterangan' => null,
            ]);

            $this->info("✅ Auto-hadir tercatat: {$user->name} pada jam {$jamMasuk} WITA");
        }

        return 0;
    }
}
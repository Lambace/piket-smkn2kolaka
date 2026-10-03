<?php

use App\Models\AbsensiPetugas;
use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment('Inspirational Quote');
})->purpose('Display an inspiring quote');

// ===== HELPER: Tanggal & hari zona Asia/Makassar =====
$todayWita = fn () => Carbon::now('Asia/Makassar')->toDateString();
$hariWita  = fn () => Carbon::now('Asia/Makassar')->isoFormat('dddd');

// ===== 1) AUTO-HADIR PETUGAS (jendela 07:00–07:02, cadangan 2 menit) =====
Schedule::command('piket:auto-hadir')
    ->cron('0-2 7 * * *')
    ->timezone('Asia/Makassar')
    ->withoutOverlapping()
    ->when(function () use ($todayWita, $hariWita) {
        $sudah = AbsensiPetugas::whereDate('tanggal', $todayWita())
            ->pluck('nama')
            ->map(fn ($n) => strtolower(trim($n)));

        return User::whereIn('role', ['petugas', 'koordinator'])
            ->where('auto_hadir', true)
            ->where('hari_piket', $hariWita())
            ->get()
            ->contains(fn ($u) => !$sudah->contains(strtolower(trim($u->name))));
    })
    ->onSuccess(fn () => Log::info('Auto-hadir berhasil dieksekusi.'))
    ->onFailure(fn () => Log::error('Auto-hadir gagal dieksekusi.'));

// ===== 2) BANNER TV KE GRUP SEKOLAH (jendela 15:00–15:02, cadangan 2 menit) =====
Schedule::command('tv:kirim-grup')
    ->cron('0-2 15 * * *')
    ->timezone('Asia/Makassar')
    ->withoutOverlapping()
    ->when(fn () => !Notifikasi::whereDate('created_at', $todayWita())
        ->where('jenis', 'gabungan') // ✅ DIPERBAIKI: Sesuai dengan isi command KirimTvKeGrup
        ->exists())
    ->onSuccess(fn () => Log::info('Banner TV berhasil terkirim ke grup sekolah.'))
    ->onFailure(fn () => Log::error('Banner TV gagal terkirim.'));

// ===== 3) REKAP HARIAN KE WALI KELAS (jendela 15:05–15:07, cadangan 2 menit) =====
Schedule::command('rekap:kirim-harian')
    ->cron('5-7 15 * * *')
    ->timezone('Asia/Makassar')
    ->withoutOverlapping()
    ->when(fn () => !Notifikasi::whereDate('created_at', $todayWita())
        ->where('jenis', 'rekap')
        ->exists())
    ->onSuccess(fn () => Log::info('Rekap harian berhasil dikirim ke wali kelas.'))
    ->onFailure(fn () => Log::error('Rekap harian gagal dikirim.'));
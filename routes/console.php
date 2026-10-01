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

// ===== HELPER: Tanggal & hari di zona waktu Asia/Makassar =====
$todayWita = fn () => Carbon::now('Asia/Makassar')->toDateString();
$hariWita  = fn () => Carbon::now('Asia/Makassar')->isoFormat('dddd');

// ===== 1) PEMBERSIHAN PDF LAMA (06:00 WITA setiap hari) =====
// Dipindah dari 03:00 ke 06:00 agar masuk jendela pagi cron-job.org.
Schedule::command('laporan:bersih-pdf')
    ->cron('0 6 * * *')
    ->timezone('Asia/Makassar');

// ===== 2) AUTO-HADIR PETUGAS (jendela 07:00–07:02 WITA, cadangan 2 menit) =====
// Hanya jalan jika masih ada petugas auto_hadir=true di hari piket
// yang BELUM tercatat absen hari ini (anti-dobel).
Schedule::command('piket:auto-hadir')
    ->cron('0-2 7 * * *')
    ->timezone('Asia/Makassar')
    ->withoutOverlapping()
    ->when(function () use ($todayWita, $hariWita) {
        $sudah = AbsensiPetugas::whereDate('tanggal', $todayWita())
            ->pluck('nama')
            ->map(fn ($n) => strtolower(trim($n)));

        $target = User::whereIn('role', ['petugas', 'koordinator'])
            ->where('auto_hadir', true)
            ->where('hari_piket', $hariWita())
            ->get();

        return $target->contains(fn ($u) => !$sudah->contains(strtolower(trim($u->name))));
    })
    ->onSuccess(fn () => Log::info('Auto-hadir berhasil dieksekusi.'))
    ->onFailure(fn () => Log::error('Auto-hadir gagal dieksekusi.'));

// ===== 3) BANNER TV KE GRUP SEKOLAH (jendela 15:00–15:02 WITA, cadangan 2 menit) =====
// Gerbang idempoten: cegah banner dobel. Skip jika sudah ada notifikasi
// ke grup (@g.us) yang tercatat hari ini, apapun statusnya.
// Gatekeeper internal command tetap berlaku: skip Minggu, skip tanpa koordinator.
Schedule::command('tv:kirim-grup')
    ->cron('0-2 15 * * *')
    ->timezone('Asia/Makassar')
    ->withoutOverlapping()
    ->when(fn () => !Notifikasi::whereDate('created_at', $todayWita())
        ->where('nomor_tujuan', 'like', '%@g.us')
        ->exists())
    ->onSuccess(fn () => Log::info('Banner TV berhasil terkirim ke grup sekolah.'))
    ->onFailure(fn () => Log::error('Banner TV gagal terkirim (jendela 15:00-15:02).'));

// ===== 4) REKAP HARIAN KE WALI KELAS (jendela 15:05–15:07 WITA, cadangan 2 menit) =====
// Gerbang idempoten: skip jika rekap sudah terkirim hari ini.
Schedule::command('rekap:kirim-harian')
    ->cron('5-7 15 * * *')
    ->timezone('Asia/Makassar')
    ->withoutOverlapping()
    ->when(fn () => !Notifikasi::whereDate('created_at', $todayWita())
        ->where('jenis', 'rekap')
        ->exists())
    ->onSuccess(fn () => Log::info('Rekap harian berhasil dikirim ke wali kelas.'))
    ->onFailure(fn () => Log::error('Rekap harian gagal dikirim.'));

// ===== 5) PDF LAPORAN + LINK LIVE VIEW (jendela 15:10–15:12 WITA, cadangan 2 menit) =====
// Gerbang idempoten: skip jika PDF hari ini sudah tercatat terkirim.
Schedule::command('laporan:kirim-pdf')
    ->cron('10-12 15 * * *')
    ->timezone('Asia/Makassar')
    ->withoutOverlapping()
    ->when(fn () => !Notifikasi::whereDate('created_at', $todayWita())
        ->where('jenis', 'pdf')
        ->exists())
    ->onSuccess(fn () => Log::info('PDF laporan berhasil dikirim.'))
    ->onFailure(fn () => Log::error('PDF laporan gagal dikirim.'));
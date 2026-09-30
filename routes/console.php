<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment('Inspirational Quote');
})->purpose('Display an inspiring quote');

// ===== AUTO HADIR (07:00 WITA setiap hari) =====
// Petugas dengan auto_hadir=true akan tercatat hadir di hari piket mereka.
// Hari tanpa jadwal auto-hadir akan dilewati senyap (log: "Tidak ada target").
Schedule::command('piket:auto-hadir')
    ->dailyAt('07:00')
    ->timezone('Asia/Makassar');

// ===== BANNER TV KE GRUP SEKOLAH (15:00 WITA setiap hari) =====
// Gatekeeper otomatis: skip Minggu, skip hari tanpa koordinator, skip tanpa token.
// Banner dikirim dari nomor koordinator bertugas (via WaRelayService).
Schedule::command('tv:kirim-grup')
    ->dailyAt('15:00')
    ->timezone('Asia/Makassar')
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Banner TV berhasil terkirim ke grup sekolah.');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Banner TV gagal terkirim.');
    });

// ===== REKAP HARIAN KE WALI KELAS (15:05 WITA setiap hari) =====
// Rekap dikirim ke WA pribadi wali kelas yang kelasnya punya aktivitas hari ini.
// Pakai device Fonnte koordinator hari itu (fallback device sistem).
Schedule::command('rekap:kirim-harian')
    ->dailyAt('15:05')
    ->timezone('Asia/Makassar')
    ->withoutOverlapping();

// ===== PDF LAPORAN + LINK LIVE VIEW (15:10 WITA setiap hari) =====
// PDF disimpan 2 hari (link aktif) lalu dibersihkan otomatis.
Schedule::command('laporan:kirim-pdf')
    ->dailyAt('15:10')
    ->timezone('Asia/Makassar')
    ->withoutOverlapping();

// ===== PEMBERSIHAN PDF LAMA (03:00 WITA setiap hari) =====
Schedule::command('laporan:bersih-pdf')
    ->dailyAt('03:00')
    ->timezone('Asia/Makassar');
<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment('Inspirational Quote');
})->purpose('Display an inspiring quote');

// ===== REKAP HARIAN OTOMATIS KE WALI KELAS =====
// Sabtu 15:00 WITA - pesan pribadi ke setiap wali kelas
Schedule::command('rekap:kirim-harian')
    ->timezone('Asia/Makassar')
    ->saturdays()
    ->at('15:00')
    ->withoutOverlapping();

// ===== LAPORAN PDF + LIVE VIEW KE GRUP SEKOLAH =====
// Sabtu 15:02 WITA - 1x kirim berisi file PDF + link Live View di caption
Schedule::command('laporan:kirim-pdf')
    ->timezone('Asia/Makassar')
    ->saturdays()
    ->at('15:02')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Laporan PDF gagal terkirim ke grup WA');
    })
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Laporan PDF berhasil terkirim ke grup WA');
    });

// ===== PEMBERSIHAN FILE PDF LAMA =====
// Setiap hari pukul 03:00 WITA - hapus PDF berusia lebih dari 2 hari
Schedule::command('laporan:bersih-pdf')
    ->timezone('Asia/Makassar')
    ->dailyAt('03:00');

    // ===== Auto Hadir =====
    Schedule::command('piket:auto-hadir')
    ->saturdays()                 // ← hanya hari Sabtu
    ->at('07:31')
    ->timezone('Asia/Makassar');
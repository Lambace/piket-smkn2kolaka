<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment('Inspirational Quote');
})->purpose('Display an inspiring quote');

// ===== REKAP HARIAN OTOMATIS KE WALI KELAS =====
// Jalan setiap hari pukul 15:00 (zona waktu aplikasi)
Schedule::command('rekap:kirim-harian')
    ->timezone('Asia/Makassar')
    ->saturdays()
    ->at('15:00')
    ->withoutOverlapping();
// ===== BARU: BANNER TIM PIKET KHUSUS SABTU =====
// Jalan setiap hari Sabtu pukul 15:02 WITA
Schedule::command('tv:kirim-grup')
    ->timezone('Asia/Makassar')
    ->saturdays()
    ->at('15:02')
    ->withoutOverlapping();
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('❌ Laporan PDF gagal terkirim ke grup WA');
    })
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('✅ Laporan PDF berhasil terkirim ke grup WA');
    });
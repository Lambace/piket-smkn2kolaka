<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment('Inspirational Quote');
})->purpose('Display an inspiring quote');

// ===== REKAP HARIAN OTOMATIS KE WALI KELAS =====
// Jalan setiap hari pukul 15:00 (zona waktu aplikasi)
Schedule::command('rekap:kirim-harian')->dailyAt('15:00');
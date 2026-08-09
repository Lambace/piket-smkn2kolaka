<?php

namespace App\Providers;

use App\Models\IzinKeluar;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Otomatisasi izin keluar: hanya untuk request web (bukan perintah console)
        if (! $this->app->runningInConsole()) {
            try {
                if (Schema::hasTable('izin_keluar')) {
                    IzinKeluar::tutupOtomatis();
                }
            } catch (\Throwable $e) {
                // Abaikan aman — misalnya tabel belum ada saat migrate
            }
        }
    }
}
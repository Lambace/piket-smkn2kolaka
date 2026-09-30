<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

class PengirimWaResolver
{
    /**
     * Koordinator piket HARI INI yang punya nomor WA.
     * Prioritas: role koordinator, lalu petugas terlama.
     */
    public function resolve(): ?User
    {
        $hariIni = Carbon::now('Asia/Makassar')->isoFormat('dddd');

        return User::where('hari_piket', $hariIni)
            ->whereNotNull('no_wa')
            ->where('no_wa', '!=', '')
            ->orderByRaw("role = 'koordinator' DESC")
            ->orderBy('name')
            ->first();
    }
}
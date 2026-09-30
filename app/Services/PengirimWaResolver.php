<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

class PengirimWaResolver
{
    /**
     * Koordinator piket AKTIF untuk hari tertentu (default: hari ini).
     * Syarat aktif:
     *  - jadwal piket = hari tersebut
     *  - punya No. WA
     *  - punya Token Fonnte (agar benar-benar bisa mengirim)
     * Prioritas: role koordinator dahulu, lalu urutan nama (stabil).
     * Return null jika hari itu tidak ada koordinator siap → pemanggil wajib skip.
     */
    public function resolve(?string $hari = null): ?User
    {
        $hari ??= Carbon::now('Asia/Makassar')->isoFormat('dddd');

        return User::whereIn('role', ['koordinator', 'petugas'])
            ->where('hari_piket', $hari)
            ->whereNotNull('no_wa')
            ->where('no_wa', '!=', '')
            ->whereNotNull('fonnte_token')
            ->where('fonnte_token', '!=', '')
            ->orderByRaw("role = 'koordinator' DESC")
            ->orderBy('name')
            ->first();
    }
}
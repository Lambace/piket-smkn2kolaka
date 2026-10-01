<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RestrictPetugasOffDay
{
    protected array $allowedRoutes = [
        'dashboard',
        'absensi.index',
        'absensi.store',
        'absensi-petugas.update',
        'absensi-petugas.destroy',
        'profile.edit',
        'profile.update',
        'profile.destroy',
        'logout',
        'notifikasi.index',
        'monitoring.index',
        'tampil',
        'tampil.laporan',
        'tampil.daftar-hadir',
        'banner.piket',
        'logo.sekolah',
        'logo.instansi',
        'papan.informasi',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if (in_array($user->role, ['koordinator', 'wakasek'])) {
            return $next($request);
        }

        $hariIni = Carbon::now('Asia/Makassar')->isoFormat('dddd');
        $currentRoute = $request->route()?->getName();

        if ($user->hari_piket !== $hariIni) {
            if ($currentRoute && in_array($currentRoute, $this->allowedRoutes)) {
                return $next($request);
            }

            Log::info('[RESTRICT] Petugas ' . $user->name . ' (piket=' . ($user->hari_piket ?? '-') . ') diblokir dari ' . $currentRoute . ' di hari ' . $hariIni);

            return redirect()
                ->route('absensi.index')
                ->with('error', 'Hari ini bukan jadwal piket Anda. Silakan periksa kembali jadwal Anda.');
        }

        return $next($request);
    }
}
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RestrictPetugasOffDay
{
    /**
     * Route yang TETAP boleh diakses petugas di luar hari piket.
     * Ketat: hanya hub overlay (absensi) dan logout.
     * ('tampil' / Live View adalah route PUBLIK — tidak perlu whitelist.)
     */
    protected array $allowedRoutes = [
        'absensi.index',   // hub overlay "Bukan Jadwal Piket Anda"
        'logout',

        // Opsional — hapus tanda // jika suatu saat ingin profil bisa diakses off-day:
        // 'profile.edit',
        // 'profile.update',
        // 'profile.destroy',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Koordinator & wakasek bebas ke mana saja
        if (in_array($user->role, ['koordinator', 'wakasek'])) {
            return $next($request);
        }

        $hariIni = Carbon::now('Asia/Makassar')->isoFormat('dddd');
        $currentRoute = $request->route()?->getName();

        // Petugas di luar hari piket → hanya whitelist yang lolos
        if ($user->hari_piket !== $hariIni) {
            if ($currentRoute && in_array($currentRoute, $this->allowedRoutes)) {
                return $next($request);
            }

            Log::info('[RESTRICT] Petugas ' . $user->name . ' diblokir dari ' . $currentRoute . ' (bukan hari piket)');

            return redirect()
                ->route('absensi.index')
                ->with('error', 'Hari ini bukan jadwal piket Anda. Silakan periksa kembali jadwal Anda.');
        }

        return $next($request);
    }
}
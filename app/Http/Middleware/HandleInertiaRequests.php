<?php

namespace App\Http\Middleware;

use App\Models\Pengaturan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role ?? 'koordinator', // role untuk RBAC
                ] : null,
            ],
            // ===== BARU: KEY LIVE VIEW =====
            // Hanya dibagikan jika user login, agar key tidak bocor ke halaman publik.
            // Dipakai oleh tautan "Laporan" di sidebar untuk membuka halaman Live View.
            'displayKey' => $user ? config('services.display.key') : null,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ],
            'pengaturan' => fn () => $this->pengaturan(),
        ];
    }

    private function pengaturan(): ?array
    {
        try {
            $p = Pengaturan::first();
            if (!$p) return null;

            return [
                'nama_sekolah'      => $p->nama_sekolah ?? config('app.name'),
                'nama_instansi'     => $p->nama_instansi ?? null,
                'warna_tema'        => $p->warna_tema ?? '#4f46e5',
                'logo'              => $p->logo,
                'logo_url'          => $p->logo ? $this->fileUrl($p->logo) : null,

                'logo_instansi'     => $p->logo_instansi,
                'logo_instansi_url' => $p->logo_instansi ? $this->fileUrl($p->logo_instansi) : null,
                'kop_baris1'        => $p->kop_baris1 ?? null,
                'kop_baris2'        => $p->kop_baris2 ?? null,
                'kop_nama_sekolah'  => $p->kop_nama_sekolah ?? null,
                'alamat'            => $p->alamat ?? null,
                'telepon'           => $p->telepon ?? null,
                'email'             => $p->email ?? null,
                'website'           => $p->website ?? null,
                'server'            => $p->server ?? null,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function fileUrl(string $path): string
    {
        try {
            return Storage::disk('public')->url($path);
        } catch (\Throwable $e) {
            return asset('storage/' . $path);
        }
    }
}
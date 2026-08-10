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
                    'role'  => $user->role ?? 'koordinator', // ← BARU: role untuk RBAC
                ] : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'pengaturan' => fn () => $this->pengaturan(),
        ];
    }

    private function pengaturan(): ?array
    {
        try {
            $p = Pengaturan::first();

            if (!$p) {
                return null;
            }

            return [
                'nama_sekolah' => $p->nama_sekolah ?? config('app.name'),
                'warna_tema' => $p->warna_tema ?? '#4f46e5',
                'logo' => $p->logo,
                'logo_url' => $p->logo ? $this->fileUrl($p->logo) : null,
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
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    protected string $base;
    protected string $token;

    public function __construct()
    {
        $this->base  = config('services.fonnte.base', 'https://api.fonnte.com');
        $this->token = (string) config('services.fonnte.token');
    }

    /**
     * Kirim pesan teks.
     */
    public function kirimTeks(string $target, string $message, ?string $token = null): bool
    {
        return $this->kirim(['target' => $target, 'message' => $message], $token);
    }

    /**
     * Kirim gambar via URL.
     */
    public function kirimGambar(string $target, string $url, string $caption, ?string $token = null): bool
    {
        return $this->kirim([
            'target'  => $target,
            'message' => $caption,
            'type'    => 'image',
            'url'     => $url,
        ], $token);
    }

    /**
     * Kirim dokumen (PDF, dll).
     */
    public function kirimDokumen(string $target, string $url, string $filename, string $caption, ?string $token = null): bool
    {
        return $this->kirim([
            'target'   => $target,
            'message'  => $caption,
            'type'     => 'document',
            'url'      => $url,
            'filename' => $filename,
        ], $token);
    }

        /**
     * Cek status device Fonnte (online/offline).
     * Return array: ['status' => 'online'|'offline', 'device' => string|null]
     */
    public function cekStatusDevice(?string $token = null): array
    {
        $tokenAktif = ($token !== null && $token !== '') ? $token : $this->token;
        $device = ($token !== null && $token !== '') ? 'koordinator' : 'sistem';

        try {
            $res = Http::withHeaders(['Authorization' => $tokenAktif])
                ->timeout(10)
                ->get($this->base . '/status');

            if ($res->successful()) {
                $body = $res->json() ?? [];
                $status = ($body['status'] ?? false) ? 'online' : 'offline';
                $deviceName = $body['device'] ?? null;
                return ['status' => $status, 'device' => $deviceName, 'source' => $device];
            }
        } catch (\Throwable $e) {
            Log::error('[FONNTE] Cek status gagal: ' . $e->getMessage());
        }

        return ['status' => 'offline', 'device' => null, 'source' => $device];
    }

    protected function kirim(array $payload, ?string $token = null): bool
    {
        $tokenAktif = ($token !== null && $token !== '') ? $token : $this->token;
        $device     = ($token !== null && $token !== '') ? 'koordinator' : 'sistem';

        try {
            $res = Http::withHeaders(['Authorization' => $tokenAktif])
                ->asForm()
                ->timeout(60)
                ->post($this->base . '/send', $payload);

            $ok = $res->successful() && ($res->json('status') ?? false);

            if (!$ok) {
                Log::warning('[FONNTE] Gagal ke ' . $payload['target'] . ' (device ' . $device . '): ' . $res->body());
            } else {
                Log::info('[FONNTE] Terkirim ke ' . $payload['target'] . ' via device ' . $device);
            }

            return $ok;
        } catch (\Throwable $e) {
            Log::error('[FONNTE] Error: ' . $e->getMessage());
            return false;
        }
    }
}
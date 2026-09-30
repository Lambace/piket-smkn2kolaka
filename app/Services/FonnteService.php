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
     * $token opsional = token Fonnte koordinator (pengirim = nomor koordinator).
     * Null / kosong = pakai token sistem (config services.fonnte.token).
     */
    public function kirimTeks(string $to, string $message, ?string $token = null): bool
    {
        return $this->kirim(['to' => $to, 'message' => $message], $token);
    }

    public function kirimGambar(string $to, string $url, string $caption, ?string $token = null): bool
    {
        return $this->kirim([
            'to' => $to, 'message' => $caption, 'type' => 'image', 'url' => $url,
        ], $token);
    }

    public function kirimDokumen(string $to, string $url, string $filename, string $caption, ?string $token = null): bool
    {
        return $this->kirim([
            'to' => $to, 'message' => $caption,
            'type' => 'document', 'url' => $url, 'filename' => $filename,
        ], $token);
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
                Log::warning('[FONNTE] Gagal ke ' . $payload['to'] . ' (device ' . $device . '): ' . $res->body());
            } else {
                Log::info('[FONNTE] Terkirim ke ' . $payload['to'] . ' via device ' . $device);
            }

            return $ok;
        } catch (\Throwable $e) {
            Log::error('[FONNTE] Error: ' . $e->getMessage());
            return false;
        }
    }
}
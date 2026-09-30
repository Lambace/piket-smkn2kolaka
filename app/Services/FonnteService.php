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
        $this->base  = config('services.fonnte.base', 'https://rest.fonnte.com');
        $this->token = (string) config('services.fonnte.token');
    }

    public function kirimTeks(string $to, string $message): bool
    {
        return $this->kirim(['to' => $to, 'message' => $message]);
    }

    public function kirimGambar(string $to, string $url, string $caption): bool
    {
        return $this->kirim(['to' => $to, 'message' => $caption, 'type' => 'image', 'url' => $url]);
    }

    public function kirimDokumen(string $to, string $url, string $filename, string $caption): bool
    {
        return $this->kirim([
            'to' => $to, 'message' => $caption,
            'type' => 'document', 'url' => $url, 'filename' => $filename,
        ]);
    }

    protected function kirim(array $payload): bool
    {
        try {
            $res = Http::withHeaders(['Authorization' => $this->token])
                ->asForm()->timeout(30)
                ->post($this->base . '/send', $payload);

            $ok = $res->successful() && ($res->json('status') ?? false);
            if (!$ok) Log::warning('[FONNTE] Gagal: ' . $res->body());
            return $ok;
        } catch (\Throwable $e) {
            Log::error('[FONNTE] Error: ' . $e->getMessage());
            return false;
        }
    }
}
<?php

namespace App\Services;

use App\Models\Notifikasi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function kirim(string $nomor, string $pesan, $penerima = null): Notifikasi
    {
        // Normalisasi: 0812... -> 62812...
        $nomor = preg_replace('/[^0-9]/', '', $nomor);
        if (str_starts_with($nomor, '0')) {
            $nomor = '62' . substr($nomor, 1);
        }

        $notifikasi = Notifikasi::create([
            'jenis' => 'whatsapp',
            'penerima_tipe' => $penerima ? get_class($penerima) : null,
            'penerima_id' => $penerima ? $penerima->id : null,
            'nomor_tujuan' => $nomor,
            'pesan' => $pesan,
            'status' => 'menunggu',
        ]);

        $token = config('services.fonnte.token');

        if (!$token) {
            $notifikasi->update([
                'status' => 'gagal',
                'pesan_error' => 'Token Fonnte belum diatur di .env',
            ]);
            return $notifikasi;
        }

        try {
            $response = Http::asForm()->withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $nomor,
                'message' => $pesan,
            ]);

            if ($response->successful() && ($response->json('status') === true || $response->json('status') === 'success')) {
                $notifikasi->update([
                    'status' => 'terkirim',
                    'terkirim_pada' => now(),
                ]);
            } else {
                $notifikasi->update([
                    'status' => 'gagal',
                    'pesan_error' => $response->json('reason') ?? 'Respon API tidak valid',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Gagal kirim WA: ' . $e->getMessage());
            $notifikasi->update([
                'status' => 'gagal',
                'pesan_error' => $e->getMessage(),
            ]);
        }

        return $notifikasi;
    }
}
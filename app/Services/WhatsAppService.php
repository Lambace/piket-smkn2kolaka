<?php

namespace App\Services;

use App\Models\Notifikasi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhatsAppService
{
    private string $apiUrl = 'https://api.fonnte.com/send';

    // ===== METHOD LAMA (kirim pesan teks ke nomor WA) =====
    public function kirim(string $nomor, string $pesan, $penerima = null): Notifikasi
    {
        $nomor = $this->normalisasiNomor($nomor);

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
            ])->timeout(30)->post($this->apiUrl, [
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

    // ===== BARU: Kirim gambar dengan caption =====
    public function sendImage(string $target, string $imageUrl, string $caption = '', $penerima = null): Notifikasi
    {
        $target = $this->normalisasiTarget($target);

        $notifikasi = Notifikasi::create([
            'jenis' => 'whatsapp',
            'penerima_tipe' => $penerima ? get_class($penerima) : null,
            'penerima_id' => $penerima ? $penerima->id : null,
            'nomor_tujuan' => $target,
            'pesan' => '[GAMBAR] '.$caption,
            'status' => 'menunggu',
        ]);

        $token = config('services.fonnte.token');

        if (!$token) {
            $notifikasi->update([
                'status' => 'gagal',
                'pesan_error' => 'Token Fonnte belum diatur',
            ]);
            return $notifikasi;
        }

        try {
            $response = Http::asForm()->withHeaders([
                'Authorization' => $token,
            ])->timeout(60)->post($this->apiUrl, [
                'target'  => $target,
                'url'     => $imageUrl,
                'caption' => $caption,
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
            Log::error('Gagal kirim gambar WA: ' . $e->getMessage());
            $notifikasi->update([
                'status' => 'gagal',
                'pesan_error' => $e->getMessage(),
            ]);
        }

        return $notifikasi;
    }

    // ===== BARU: Kirim file PDF ke grup/nomor WA =====
    public function kirimPdf(string $target, string $pdfUrl, string $filename, string $caption = ''): Notifikasi
    {
        $target = $this->normalisasiTarget($target);

        $notifikasi = Notifikasi::create([
            'jenis' => 'whatsapp',
            'penerima_tipe' => null,
            'penerima_id' => null,
            'nomor_tujuan' => $target,
            'pesan' => '[PDF] '.$filename.' - '.$caption,
            'status' => 'menunggu',
        ]);

        $token = config('services.fonnte.token');

        if (!$token) {
            $notifikasi->update([
                'status' => 'gagal',
                'pesan_error' => 'Token Fonnte belum diatur',
            ]);
            return $notifikasi;
        }

        try {
            $response = Http::asForm()->withHeaders([
                'Authorization' => $token,
            ])->timeout(120)->post($this->apiUrl, [
                'target'   => $target,
                'url'      => $pdfUrl,
                'filename' => $filename,
                'caption'  => $caption,
            ]);

            $body = $response->json() ?? [];

            Log::info('Fonnte kirim PDF', [
                'target'   => $target,
                'filename' => $filename,
                'response' => $body,
            ]);

            if ($response->successful() && ($body['status'] === true || $body['status'] === 'success')) {
                $notifikasi->update([
                    'status' => 'terkirim',
                    'terkirim_pada' => now(),
                ]);
            } else {
                $notifikasi->update([
                    'status' => 'gagal',
                    'pesan_error' => $body['reason'] ?? 'Respon API tidak valid',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Gagal kirim PDF WA: ' . $e->getMessage());
            $notifikasi->update([
                'status' => 'gagal',
                'pesan_error' => $e->getMessage(),
            ]);
        }

        return $notifikasi;
    }

    // ===== HELPER: Normalisasi nomor HP (0812 → 62812) =====
    private function normalisasiNomor(string $nomor): string
    {
        $nomor = preg_replace('/[^0-9]/', '', $nomor);
        if (str_starts_with($nomor, '0')) {
            $nomor = '62' . substr($nomor, 1);
        }
        return $nomor;
    }

    // ===== HELPER: Normalisasi target (dukung grup WA @g.us) =====
    private function normalisasiTarget(string $target): string
    {
        // Kalau sudah format grup (628xxx@g.us atau 628xxx@c.us), biarkan
        if (str_contains($target, '@g.us') || str_contains($target, '@c.us')) {
            return $target;
        }

        // Kalau nomor biasa, normalisasi
        return $this->normalisasiNomor($target);
    }
}
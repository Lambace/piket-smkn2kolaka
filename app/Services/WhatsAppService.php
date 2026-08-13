<?php

namespace App\Services;

use App\Models\Notifikasi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiUrl = 'https://api.fonnte.com/send';

    // ===== Kirim pesan teks =====
    public function kirim(string $nomor, string $pesan, $penerima = null): Notifikasi
    {
        $nomor = $this->normalisasiNomor($nomor);
        $notifikasi = $this->buatNotifikasi($nomor, $pesan, $penerima);

        return $this->kirimKeFonnte($notifikasi, [
            'target'  => $nomor,
            'message' => $pesan,
        ], 30);
    }

    // ===== Kirim gambar + caption =====
    public function sendImage(string $target, string $imageUrl, string $caption = '', $penerima = null): Notifikasi
    {
        $target = $this->normalisasiTarget($target);
        $notifikasi = $this->buatNotifikasi($target, '[GAMBAR] '.$caption, $penerima);

        return $this->kirimKeFonnte($notifikasi, [
            'target'  => $target,
            'url'     => $imageUrl,
            'caption' => $caption,
        ], 60);
    }

    // ===== Kirim file PDF =====
    public function kirimPdf(string $target, string $pdfUrl, string $filename, string $caption = ''): Notifikasi
    {
        $target = $this->normalisasiTarget($target);
        $notifikasi = $this->buatNotifikasi($target, '[PDF] '.$filename, null);

        return $this->kirimKeFonnte($notifikasi, [
            'target'   => $target,
            'url'      => $pdfUrl,
            'filename' => $filename,
            'caption'  => $caption,
        ], 120);
    }

    // ===== HELPER: buat record notifikasi (aman untuk grup) =====
    private function buatNotifikasi(string $target, string $pesan, $penerima = null): Notifikasi
    {
        return Notifikasi::create([
            'jenis'         => 'whatsapp',
            // FIX: isi default 'grup' & 0 saat tidak ada penerima (kolom NOT NULL)
            'penerima_tipe' => $penerima ? get_class($penerima) : 'grup',
            'penerima_id'   => $penerima ? $penerima->id : 0,
            'nomor_tujuan'  => $target,
            'pesan'         => $pesan,
            'status'        => 'menunggu',
        ]);
    }

    // ===== HELPER: panggil API Fonnte + update status =====
    private function kirimKeFonnte(Notifikasi $notifikasi, array $payload, int $timeout): Notifikasi
    {
        $token = config('services.fonnte.token');

        if (!$token) {
            $notifikasi->update([
                'status'      => 'gagal',
                'pesan_error' => 'Token Fonnte belum diatur',
            ]);
            return $notifikasi;
        }

        try {
            $response = Http::asForm()->withHeaders([
                'Authorization' => $token,
            ])->timeout($timeout)->post($this->apiUrl, $payload);

            $body = $response->json() ?? [];

            Log::info('Fonnte response', ['target' => $payload['target'], 'body' => $body]);

            if ($response->successful() && ($body['status'] === true || $body['status'] === 'success')) {
                $notifikasi->update([
                    'status'        => 'terkirim',
                    'terkirim_pada' => now(),
                ]);
            } else {
                $notifikasi->update([
                    'status'      => 'gagal',
                    'pesan_error' => $body['reason'] ?? 'Respon API tidak valid',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Gagal kirim WA: '.$e->getMessage());
            $notifikasi->update([
                'status'      => 'gagal',
                'pesan_error' => $e->getMessage(),
            ]);
        }

        return $notifikasi;
    }

    // ===== HELPER: normalisasi nomor (0812 -> 62812) =====
    private function normalisasiNomor(string $nomor): string
    {
        $nomor = preg_replace('/[^0-9]/', '', $nomor);
        if (str_starts_with($nomor, '0')) {
            $nomor = '62'.substr($nomor, 1);
        }
        return $nomor;
    }

    // ===== HELPER: dukung target grup (@g.us) =====
    private function normalisasiTarget(string $target): string
    {
        if (str_contains($target, '@g.us') || str_contains($target, '@c.us')) {
            return $target;
        }
        return $this->normalisasiNomor($target);
    }
}
<?php

namespace App\Services;

use App\Models\Notifikasi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiUrl = 'https://api.fonnte.com/send';

    // ===== Kirim pesan teks =====
    // $token opsional: token Fonnte koordinator → pesan keluar dari NOMOR KOORDINATOR.
    // Null / kosong = token sistem (config services.fonnte.token).
    public function kirim(string $nomor, string $pesan, $penerima = null, ?string $token = null): Notifikasi
    {
        // ===== FIX: pakai normalisasiTarget agar ID grup @g.us tidak dirusak =====
        $nomor = $this->normalisasiTarget($nomor);
        $notifikasi = $this->buatNotifikasi($nomor, $pesan, $penerima);

        return $this->kirimKeFonnte($notifikasi, [
            'target'  => $nomor,
            'message' => $pesan,
        ], 30, null, null, $token);
    }

    // ===== Kirim gambar via URL (untuk banner/logo) =====
    public function sendImage(string $target, string $imageUrl, string $caption = '', $penerima = null, ?string $token = null): Notifikasi
    {
        $target = $this->normalisasiTarget($target);
        $notifikasi = $this->buatNotifikasi($target, '[GAMBAR] '.$caption, $penerima);

        return $this->kirimKeFonnte($notifikasi, [
            'target'  => $target,
            'message' => $caption,
            'url'     => $imageUrl,
        ], 60, null, null, $token);
    }

    // ===== KIRIM PDF: upload binary LANGSUNG (icon PDF asli di WA) =====
    public function kirimPdf(string $target, string $pdfContent, string $filename, string $caption = '', ?string $token = null): Notifikasi
    {
        $target = $this->normalisasiTarget($target);
        $notifikasi = $this->buatNotifikasi($target, '[PDF] '.$filename, null);

        return $this->kirimKeFonnte($notifikasi, [
            'target'   => $target,
            'message'  => $caption,
            'filename' => $filename,
        ], 120, $pdfContent, $filename, $token);
    }

    // ===== HELPER: buat record notifikasi =====
    private function buatNotifikasi(string $target, string $pesan, $penerima = null): Notifikasi
    {
        return Notifikasi::create([
            'jenis'         => 'whatsapp',
            'penerima_tipe' => $penerima ? get_class($penerima) : 'grup',
            'penerima_id'   => $penerima ? $penerima->id : 0,
            'nomor_tujuan'  => $target,
            'pesan'         => $pesan,
            'status'        => 'menunggu',
        ]);
    }

    // ===== HELPER: panggil Fonnte (dukung upload file binary + multi-device) =====
    private function kirimKeFonnte(
        Notifikasi $notifikasi,
        array $payload,
        int $timeout,
        ?string $fileContent = null,
        ?string $fileName = null,
        ?string $token = null,
    ): Notifikasi {
        $tokenAktif = ($token !== null && $token !== '') ? $token : config('services.fonnte.token');
        $device     = ($token !== null && $token !== '') ? 'koordinator' : 'sistem';

        if (!$tokenAktif) {
            $notifikasi->update([
                'status'      => 'gagal',
                'pesan_error' => 'Token Fonnte belum diatur',
            ]);
            return $notifikasi;
        }

        try {
            $request = Http::withHeaders(['Authorization' => $tokenAktif])->timeout($timeout);

            if ($fileContent !== null) {
                $response = $request
                    ->attach('file', $fileContent, $fileName)
                    ->post($this->apiUrl, $payload);
            } else {
                $response = $request->asForm()->post($this->apiUrl, $payload);
            }

            $body = $response->json() ?? [];

            Log::info('Fonnte response', [
                'target' => $payload['target'],
                'device' => $device,
                'body'   => $body,
            ]);

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

    private function normalisasiNomor(string $nomor): string
    {
        $nomor = preg_replace('/[^0-9]/', '', $nomor);
        if (str_starts_with($nomor, '0')) {
            $nomor = '62'.substr($nomor, 1);
        }
        return $nomor;
    }

    private function normalisasiTarget(string $target): string
    {
        if (str_contains($target, '@g.us') || str_contains($target, '@c.us')) {
            return $target;
        }
        return $this->normalisasiNomor($target);
    }
}
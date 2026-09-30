<?php

namespace App\Services;

use App\Models\Pengaturan;
use Illuminate\Support\Facades\Log;

class WaRelayService
{
    public function __construct(
        protected FonnteService $fonnte,
        protected PengirimWaResolver $resolver,
    ) {}

    /**
     * OPSI 1 (RELAY): PDF + caption dikirim ke WA pribadi koordinator aktif,
     * lalu koordinator mem-forward ke Grup Wali Kelas & Grup Orang Tua.
     * FALLBACK: jika koordinator hari ini tidak punya no_wa,
     * kirim langsung ke grup (pengirim = nomor sistem).
     */
    public function kirimLaporan(string $pdfUrl, string $filename, string $caption): bool
    {
        $pengaturan  = Pengaturan::first();
        $koordinator = $this->resolver->resolve();

        $ttd = $koordinator
            ? "\n\nHormat kami,\n*" . $koordinator->name . "*\nKoordinator Piket Hari Ini"
            : '';

        if ($koordinator) {
            // 1) PDF laporan masuk ke WA koordinator
            $ok = $this->fonnte->kirimDokumen(
                $koordinator->no_wa, $pdfUrl, $filename, $caption . $ttd
            );

            // 2) Instruksi forward
            $this->fonnte->kirimTeks($koordinator->no_wa,
                "⬆️ Laporan piket hari ini sudah siap.\n" .
                "Mohon *TERUSKAN (forward)* pesan di atas ke:\n" .
                "1️⃣ Grup Wali Kelas\n" .
                "2️⃣ Grup Orang Tua\n\n" .
                "Terima kasih 🙏"
            );

            Log::info('[WA-RELAY] Laporan dititipkan ke: ' . $koordinator->name . ' (' . $koordinator->no_wa . ')');
            return $ok;
        }

        // FALLBACK: kirim langsung ke grup
        Log::warning('[WA-RELAY] Koordinator hari ini tanpa no_wa → kirim langsung ke grup');
        $ok = true;
        foreach (array_filter([$pengaturan?->wa_grup, $pengaturan?->wa_grup_orang_tua]) as $grup) {
            $ok = $this->fonnte->kirimDokumen($grup, $pdfUrl, $filename, $caption . $ttd) && $ok;
        }
        return $ok;
    }
}
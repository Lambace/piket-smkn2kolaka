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
     * Kirim banner TV ke GRUP SEKOLAH memakai nomor koordinator bertugas.
     *
     * $hari opsional: hari sasaran (untuk simulasi --hari=Rabu).
     * Null = hari ini otomatis (perilaku scheduler harian).
     *
     * Strategi 3 lapis:
     *  1) LANGSUNG ke grup sekolah pakai device koordinator
     *  2) RELAY: banner ke WA pribadi koordinator + instruksi forward
     *  3) FALLBACK: langsung ke grup sekolah pakai device sistem
     */
    public function kirimBanner(string $bannerUrl, string $caption, ?string $hari = null): bool
    {
        $pengaturan  = Pengaturan::first();
        $grupSekolah = $pengaturan?->wa_grup ?: env('WA_GROUP_ID');

        // ===== FIX: $hari kini parameter resmi method =====
        $koordinator = $this->resolver->resolve($hari);
        $labelHari   = $hari ?? \Illuminate\Support\Carbon::now('Asia/Makassar')->isoFormat('dddd');

        if (empty($grupSekolah)) {
            Log::error('[WA-RELAY] Grup sekolah belum diisi (pengaturan.wa_grup atau WA_GROUP_ID).');
            return false;
        }

        // Gatekeeper: tanpa koordinator aktif → skip senyap
        if (!$koordinator) {
            Log::info('[WA-RELAY] Tidak ada koordinator aktif hari ' . $labelHari . ' → banner dilewati.');
            return false;
        }

        $ttd   = "\n\nHormat kami,\n*" . $koordinator->name . "*\nKoordinator Piket Hari Ini";
        $pesan = $caption . $ttd;
        $token = $koordinator->fonnte_token;

        // ----- Lapis 1: langsung ke grup sekolah (device koordinator) -----
        if ($this->fonnte->kirimGambar($grupSekolah, $bannerUrl, $pesan, $token)) {
            Log::info('[WA-RELAY] Banner → Grup Sekolah via device ' . $koordinator->name);
            return true;
        }

        // ----- Lapis 2: relay via WA pribadi koordinator -----
        Log::warning('[WA-RELAY] Langsung ke grup gagal (mungkin nomor koordinator tidak di grup) → coba relay pribadi.');
        if ($this->fonnte->kirimGambar($koordinator->no_wa, $bannerUrl, $pesan, $token)) {
            $this->fonnte->kirimTeks(
                $koordinator->no_wa,
                "⬆️ Banner laporan piket hari ini sudah siap.\n" .
                "Mohon *TERUSKAN (forward)* pesan di atas ke *Grup Sekolah*.\n\n" .
                "Terima kasih 🙏",
                $token
            );
            Log::info('[WA-RELAY] Banner dititipkan ke: ' . $koordinator->name . ' (' . $koordinator->no_wa . ')');
            return true;
        }

        // ----- Lapis 3: fallback device sistem -----
        Log::warning('[WA-RELAY] Device koordinator gagal total → fallback device sistem.');
        if ($this->fonnte->kirimGambar($grupSekolah, $bannerUrl, $pesan, null)) {
            Log::info('[WA-RELAY] Banner → Grup Sekolah via device sistem (fallback).');
            return true;
        }

        Log::error('[WA-RELAY] Semua jalur pengiriman banner gagal.');
        return false;
    }
}
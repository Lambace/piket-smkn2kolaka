<?php

namespace App\Services;

use App\Models\IzinKeluar;
use App\Models\Keterlambatan;
use App\Models\Notifikasi;
use App\Models\Pelanggaran;
use App\Models\WaliKelas;
use Illuminate\Support\Carbon;

class RekapHarianService
{
    public function __construct(protected WhatsAppService $wa)
    {
    }

    /**
     * Kirim rekap ke semua wali kelas aktif yang kelasnya punya aktivitas hari ini.
     */
    public function kirimSemua(?Carbon $tanggal = null): array
    {
        $tanggal ??= Carbon::today();
        $hasil = ['dikirim' => 0, 'dilewati_sudah' => 0, 'dilewati_bersih' => 0, 'gagal' => 0];

        $waliKelasList = WaliKelas::where('aktif', true)
            ->whereNotNull('telepon')
            ->where('telepon', '!=', '')
            ->get();

        // ===== AMBIL DAFTAR KELAS YANG PUNYA AKTIVITAS HARI INI =====
        $kelasTerlambat = Keterlambatan::whereDate('tanggal', $tanggal)
            ->join('siswa', 'siswa.id', '=', 'keterlambatan.siswa_id')
            ->distinct()->pluck('siswa.kelas');

        $kelasIzin = IzinKeluar::whereDate('tanggal', $tanggal)
            ->join('siswa', 'siswa.id', '=', 'izin_keluar.siswa_id')
            ->distinct()->pluck('siswa.kelas');

        $kelasPelanggaran = Pelanggaran::whereDate('tanggal', $tanggal)
            ->join('siswa', 'siswa.id', '=', 'pelanggaran.siswa_id')
            ->distinct()->pluck('siswa.kelas');

        $kelasAktif = $kelasTerlambat->merge($kelasIzin)->merge($kelasPelanggaran)->unique()->values();

        // Jika tidak ada aktivitas sama sekali, tidak perlu kirim apapun
        if ($kelasAktif->isEmpty()) {
            return $hasil;
        }

        foreach ($waliKelasList as $waliKelas) {
            // Lewati wali kelas yang kelasnya bersih (tidak ada aktivitas hari ini)
            if (! $kelasAktif->contains($waliKelas->kelas)) {
                $hasil['dilewati_bersih']++;
                continue;
            }

            // Cegah kirim dobel di hari yang sama
            $sudahKirim = Notifikasi::where('jenis', 'rekap')
                ->where('penerima_tipe', WaliKelas::class)
                ->where('penerima_id', $waliKelas->id)
                ->whereDate('created_at', $tanggal)
                ->exists();

            if ($sudahKirim) {
                $hasil['dilewati_sudah']++;
                continue;
            }

            $pesan = $this->buatPesan($waliKelas, $tanggal);

            $notifikasi = $this->wa->kirim($waliKelas->telepon, $pesan, $waliKelas);
            $notifikasi->update(['jenis' => 'rekap']);

            $notifikasi->status === 'terkirim' ? $hasil['dikirim']++ : $hasil['gagal']++;
        }

        return $hasil;
    }
    /**
     * Susun pesan rekap untuk satu wali kelas.
     */
    public function buatPesan(WaliKelas $waliKelas, Carbon $tanggal): string
    {
        $kelas = $waliKelas->kelas;

        $terlambat = Keterlambatan::with('siswa')
            ->whereDate('tanggal', $tanggal)
            ->whereHas('siswa', fn ($q) => $q->where('kelas', $kelas))
            ->orderByDesc('menit_terlambat')
            ->get();

        $izin = IzinKeluar::with('siswa')
            ->whereDate('tanggal', $tanggal)
            ->whereHas('siswa', fn ($q) => $q->where('kelas', $kelas))
            ->get();

        $pelanggaran = Pelanggaran::with('siswa')
            ->whereDate('tanggal', $tanggal)
            ->whereHas('siswa', fn ($q) => $q->where('kelas', $kelas))
            ->get();

        $pesan  = "*REKAP HARIAN {$kelas}*\n";
        $pesan .= config('app.name').' — '.$tanggal->isoFormat('dddd, D MMMM Y')."\n\n";
        $pesan .= 'Yth. '.$waliKelas->nama.",\n";
        $pesan .= "Berikut ringkasan aktivitas siswa Anda hari ini:\n\n";

        $pesan .= "⏰ *KETERLAMBATAN ({$terlambat->count()})*\n";
        if ($terlambat->isEmpty()) {
            $pesan .= "Tidak ada.\n";
        } else {
            foreach ($terlambat as $k) {
                $pesan .= '• '.$k->siswa?->nama.' — '.$k->menit_terlambat.' mnt (datang '.$k->jam_datang.")\n";
            }
        }

        $pesan .= "\n🚪 *IZIN KELUAR ({$izin->count()})*\n";
        if ($izin->isEmpty()) {
            $pesan .= "Tidak ada.\n";
        } else {
            foreach ($izin as $i) {
                $kembali = $i->jam_kembali ? ', kembali '.$i->jam_kembali : ', belum kembali';
                $pesan .= '• '.$i->siswa?->nama.' — '.$i->jenis.$kembali."\n";
            }
        }

        $pesan .= "\n⚠️ *PELANGGARAN ({$pelanggaran->count()})*\n";
        if ($pelanggaran->isEmpty()) {
            $pesan .= "Tidak ada.\n";
        } else {
            foreach ($pelanggaran as $p) {
                $pesan .= '• '.$p->siswa?->nama.' — '.$p->jenis_pelanggaran.' ('.$p->poin." poin)\n";
            }
        }

        if ($terlambat->isEmpty() && $izin->isEmpty() && $pelanggaran->isEmpty()) {
            $pesan .= "\n✅ Tidak ada catatan hari ini. Pertahankan!\n";
        }

        $pesan .= "\n- Dikirim otomatis oleh Sistem Piket";

        return $pesan;
    }
}
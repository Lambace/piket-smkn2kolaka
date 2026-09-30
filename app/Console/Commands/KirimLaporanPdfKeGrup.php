<?php

namespace App\Console\Commands;

use App\Models\AbsensiPetugas;
use App\Models\BukuTamu;
use App\Models\IzinKeluar;
use App\Models\Keterlambatan;
use App\Models\Pelanggaran;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Models\User;
use App\Services\PengirimWaResolver;
use App\Services\WaRelayService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class KirimLaporanPdfKeGrup extends Command
{
    protected $signature = 'laporan:kirim-pdf';
    protected $description = 'Generate PDF laporan harian + kirim ke grup sekolah via nomor koordinator';

    public function handle(WaRelayService $relay, PengirimWaResolver $resolver): int
    {
        $now       = now()->locale('id');
        $hariIni   = $now->isoFormat('dddd');
        $tanggal   = $now->toDateString();

        // ===== GATEKEEPER: Libur / Tanpa Koordinator =====
        if ($hariIni === 'Minggu') {
            $this->info('🗓️ Minggu — skip pengiriman PDF.');
            return Command::SUCCESS;
        }

        $koordinator = $resolver->resolve();
        if (!$koordinator) {
            $this->warn("⚠️ Hari {$hariIni} tanpa koordinator aktif → skip PDF.");
            return Command::SUCCESS;
        }

        // ===== 1. GENERATE PDF =====
        $this->info('1️⃣  Generate PDF...');
        try {
            $pdfData    = $this->generatePdfData($koordinator);
            $pdfContent = Pdf::loadView('laporan.pdf', $pdfData)
                ->setPaper('a4', 'portrait')
                ->output();
        } catch (\Throwable $e) {
            $this->error('❌ Gagal generate PDF: '.$e->getMessage());
            return Command::FAILURE;
        }

        // ===== 2. SIMPAN PDF =====
        $filename = 'Laporan-Harian-'.$tanggal.'.pdf';
        Storage::disk('public')->put('laporan/'.$filename, $pdfContent);
        $pdfUrl = route('laporan.download', $filename);
        $this->info('2️⃣  PDF tersimpan: laporan/'.$filename);

        // ===== 3. BANNER LIVE VIEW =====
        $key   = env('DISPLAY_KEY', 'piket2026');
        $urlTv = url('/tampil').'?k='.$key;

        // ===== 4. CAPTION + TTD KOORDINATOR =====
        $sekolah = Pengaturan::first()?->nama_sekolah ?? 'SMKN 2 KOLAKA';
        $hadir   = AbsensiPetugas::whereDate('tanggal', $tanggal)
            ->whereIn('status', ['tepat_waktu', 'terlambat'])->count();
        $petugasHariIni = User::whereIn('role', ['petugas', 'koordinator'])
            ->where('hari_piket', $hariIni)->count();
        $alpha = max(0, $petugasHariIni - $hadir);

        $caption = implode("\n", [
            '*LAPORAN TIM PIKET '.strtoupper($hariIni).'*',
            '_'.$sekolah.'_',
            $now->isoFormat('dddd, D MMMM Y'),
            '',
            '━━━━━━━━━━━━━━━━━━━━',
            '👥 Petugas Hadir : *'.$hadir.' orang*',
            '❌ Alpha         : *'.$alpha.' orang*',
            '━━━━━━━━━━━━━━━━━━━━',
            '',
            '🔴 *Dashboard piket hari ini*:',
            $urlTv,
            '',
            '📥 *Download PDF Laporan*:',
            $pdfUrl,
            '',
            '━━━━━━━━━━━━━━━━━━━━',
            '_© Sistem Informasi Piket_Si Piket',
        ]);

        // ===== 5. KIRIM PDF via WaRelayService =====
        $this->info('3️⃣  Kirim PDF ke grup sekolah via device koordinator...');

        // Banner sebagai preview (url gambar dummy dari logo sekolah)
        $bannerUrl = url('images/banner-bg.png');
        $ok = $relay->kirimBanner($bannerUrl, $caption);

        // PDF sebagai lampiran terpisah
        if ($ok) {
            $this->info('📎 Mengirim file PDF sebagai lampiran...');
            $ok = $this->kirimPdfLangsung($relay, $pdfUrl, $filename, $caption);
        }

        $this->info('📌 File PDF disimpan 2 hari (dibersihkan otomatis oleh laporan:bersih-pdf).');

        return $ok ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Kirim PDF sebagai dokumen.
     * Memakai FonnteService langsung dengan token koordinator.
     */
    private function kirimPdfLangsung(WaRelayService $relay, string $pdfUrl, string $filename, string $caption): bool
    {
        $koordinator = app(PengirimWaResolver::class)->resolve();
        if (!$koordinator) return false;

        $pengaturan = Pengaturan::first();
        $grup = $pengaturan?->wa_grup ?: env('WA_GROUP_ID');
        if (!$grup) return false;

        $fonnte = app(\App\Services\FonnteService::class);

        // Coba kirim PDF ke grup dengan token koordinator
        if ($fonnte->kirimDokumen($grup, $pdfUrl, $filename, $caption, $koordinator->fonnte_token)) {
            return true;
        }

        // Fallback: kirim ke WA koordinator + instruksi forward
        if ($fonnte->kirimDokumen($koordinator->no_wa, $pdfUrl, $filename, $caption, $koordinator->fonnte_token)) {
            $fonnte->kirimTeks(
                $koordinator->no_wa,
                "⬆️ PDF laporan piket hari ini.\nMohon *forward* ke *Grup Sekolah*.\n\nTerima kasih 🙏",
                $koordinator->fonnte_token
            );
            return true;
        }

        // Fallback terakhir: device sistem
        return $fonnte->kirimDokumen($grup, $pdfUrl, $filename, $caption, null);
    }

    private function generatePdfData($koordinator): array
    {
        $now     = now();
        $hariIni = $now->isoFormat('dddd');
        $dari    = $now->startOfDay();
        $sampai  = $now->endOfDay();
        $dariStr = $dari->toDateString();
        $sampaiStr = $sampai->toDateString();
        $withSiswa = 'siswa:id,nisn,nis,nama,kelas,jurusan';

        $absensiPetugas = AbsensiPetugas::whereBetween('tanggal', [$dariStr, $sampaiStr])
            ->orderBy('tanggal')->orderBy('jam_masuk')->get();

        // ===== FILTER: HANYA PETUGAS YANG JADWAL HARI INI =====
        $petugasHariIni = User::whereIn('role', ['petugas', 'koordinator'])
            ->where('hari_piket', $hariIni)
            ->orderBy('name')->get();

        $rekapPetugas = $petugasHariIni->map(function ($u) use ($dariStr, $sampaiStr, $dari) {
            $r = AbsensiPetugas::where('nama', $u->name)
                ->whereBetween('tanggal', [$dariStr, $sampaiStr])
                ->orderBy('jam_masuk')->first();

            return [
                'nama'       => $u->name,
                'jabatan'    => $u->role === 'koordinator' ? 'Koordinator Piket' : 'Guru Piket',
                'jam'        => $r?->jam_masuk ?? '-',
                'status'     => $r?->status ?? 'alpha',
                'keterangan' => $r?->keterangan ?? '',
                'tanggal'    => $r?->tanggal
                    ? $r->tanggal->isoFormat('D MMM Y')
                    : $dari->isoFormat('D MMM Y'),
            ];
        });

        $hadirHariIni = $rekapPetugas->filter(
            fn($r) => in_array($r['status'], ['tepat_waktu', 'terlambat'])
        )->count();
        $alphaHariIni = $rekapPetugas->count() - $hadirHariIni;

        $keterlambatan = Keterlambatan::with($withSiswa)
            ->whereBetween('tanggal', [$dariStr, $sampaiStr])->orderBy('tanggal')->get();
        $izinKeluar = IzinKeluar::with($withSiswa)
            ->whereBetween('tanggal', [$dariStr, $sampaiStr])->orderBy('tanggal')->get();
        $pelanggaran = Pelanggaran::with($withSiswa)
            ->whereBetween('tanggal', [$dariStr, $sampaiStr])->orderBy('tanggal')->get();
        $tamu = BukuTamu::whereBetween('tanggal_kunjungan', [$dariStr, $sampaiStr])
            ->orderBy('tanggal_kunjungan')->get();

        $perKelas = Keterlambatan::select('siswa.kelas as label', DB::raw('COUNT(*) as jumlah'))
            ->join('siswa', 'siswa.id', '=', 'keterlambatan.siswa_id')
            ->whereBetween('keterlambatan.tanggal', [$dariStr, $sampaiStr])
            ->groupBy('siswa.kelas')->orderByDesc('jumlah')->get();

        $perJurusan = Keterlambatan::select('siswa.jurusan as label', DB::raw('COUNT(*) as jumlah'))
            ->join('siswa', 'siswa.id', '=', 'keterlambatan.siswa_id')
            ->whereBetween('keterlambatan.tanggal', [$dariStr, $sampaiStr])
            ->groupBy('siswa.jurusan')->orderByDesc('jumlah')->get();

        $jenisPelanggaran = Pelanggaran::select('jenis_pelanggaran as label', DB::raw('COUNT(*) as jumlah'))
            ->whereBetween('tanggal', [$dariStr, $sampaiStr])
            ->groupBy('jenis_pelanggaran')->orderByDesc('jumlah')->limit(10)->get();

        $topPoin = Pelanggaran::select('siswa_id', DB::raw('SUM(poin) as total_poin'), DB::raw('COUNT(*) as jumlah_kasus'))
            ->with($withSiswa)->whereBetween('tanggal', [$dariStr, $sampaiStr])
            ->groupBy('siswa_id')->orderByDesc('total_poin')->limit(10)->get();

        $topTerlambat = Keterlambatan::select('siswa_id', DB::raw('COUNT(*) as jumlah'), DB::raw('AVG(menit_terlambat) as rata_menit'))
            ->with($withSiswa)->whereBetween('tanggal', [$dariStr, $sampaiStr])
            ->groupBy('siswa_id')->orderByDesc('jumlah')->limit(10)->get();

        $ringkasan = [
            ['label' => 'Total Siswa Aktif',     'nilai' => Siswa::where('aktif', true)->count().' siswa'],
            ['label' => 'Petugas Piket Hadir',   'nilai' => $hadirHariIni.' orang'],
            ['label' => 'Petugas Alpha',         'nilai' => $alphaHariIni.' orang'],
            ['label' => 'Keterlambatan Siswa',   'nilai' => $keterlambatan->count().' kejadian'],
            ['label' => 'Izin Keluar',           'nilai' => $izinKeluar->count().' kejadian'],
            ['label' => 'Pelanggaran',           'nilai' => $pelanggaran->count().' kejadian ('.$pelanggaran->sum('poin').' poin)'],
            ['label' => 'Kunjungan Tamu',        'nilai' => $tamu->count().' kunjungan'],
        ];

        $pengaturan = Pengaturan::first();

        $logo = null;
        if ($pengaturan?->logo && Storage::disk('public')->exists($pengaturan->logo)) {
            $mime = Storage::disk('public')->mimeType($pengaturan->logo) ?: 'image/png';
            $logo = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($pengaturan->logo));
        }

        $logoInstansi = null;
        if ($pengaturan?->logo_instansi && Storage::disk('public')->exists($pengaturan->logo_instansi)) {
            $mime = Storage::disk('public')->mimeType($pengaturan->logo_instansi) ?: 'image/png';
            $logoInstansi = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($pengaturan->logo_instansi));
        }

        $tempatTanggal = ($pengaturan->kota ?? 'Kolaka').', '.$now->isoFormat('D MMMM Y');

        $totalData = $absensiPetugas->count() + $keterlambatan->count() + $izinKeluar->count()
                   + $pelanggaran->count() + $tamu->count();

        return [
            'pengaturan'       => $pengaturan,
            'logo'             => $logo,
            'logoInstansi'     => $logoInstansi,
            'labelPeriode'     => 'Harian — '.$now->isoFormat('dddd, D MMMM Y'),
            'rekapPetugas'     => $rekapPetugas,
            'ringkasan'        => $ringkasan,
            'keterlambatan'    => $keterlambatan,
            'izinKeluar'       => $izinKeluar,
            'pelanggaran'      => $pelanggaran,
            'tamu'             => $tamu,
            'perKelas'         => $perKelas,
            'perJurusan'       => $perJurusan,
            'jenisPelanggaran' => $jenisPelanggaran,
            'topPoin'          => $topPoin,
            'topTerlambat'     => $topTerlambat,
            'totalData'        => $totalData,
            'dicetakOleh'      => 'Sistem Otomatis',
            'waktuCetak'       => now()->format('d-m-Y H:i'),
            'koordinator'      => $koordinator,
            'jabatanTtd'       => 'Koordinator Piket',
            'tempatTanggal'    => $tempatTanggal,
            'periode'          => 'harian',
            'filter_hari'      => 'Semua Hari',
            'tidakAdaJadwal'   => false,
            'pesanLibur'       => null,
        ];
    }
}
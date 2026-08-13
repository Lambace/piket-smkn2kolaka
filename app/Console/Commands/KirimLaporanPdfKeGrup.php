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
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class KirimLaporanPdfKeGrup extends Command
{
    protected $signature = 'laporan:kirim-pdf {--grup= : ID grup/nomor WA (default: env WA_GROUP_ID)}';
    protected $description = 'Generate PDF laporan harian, kirim file PDF beneran + banner Live View ke grup WA';

    public function handle(): int
    {
        $grup = $this->option('grup') ?? env('WA_GROUP_ID');

        if (empty($grup)) {
            $this->error('WA_GROUP_ID belum diisi di .env');
            return Command::FAILURE;
        }

        // =====================================================
        // LANGKAH 1: DomPDF generate PDF di server Laravel
        // =====================================================
        $this->info('1️⃣  Generate PDF dengan DomPDF...');
        try {
            $pdfData = $this->generatePdfData();
            $pdfContent = Pdf::loadView('laporan.pdf', $pdfData)
                ->setPaper('a4', 'portrait')
                ->output();
        } catch (\Throwable $e) {
            $this->error('❌ Gagal generate PDF: '.$e->getMessage());
            return Command::FAILURE;
        }

        // =====================================================
        // LANGKAH 2: Simpan jadi file "Laporan Harian.pdf"
        // =====================================================
        $tanggal = now()->format('Y-m-d');
        $filename = 'Laporan-Harian-'.$tanggal.'.pdf';
        $storagePath = 'laporan/'.$filename;

        Storage::disk('public')->put($storagePath, $pdfContent);
        $this->info('2️⃣  PDF tersimpan di server: '.$storagePath);

        // =====================================================
        // LANGKAH 3: Buat link publik untuk file itu
        // =====================================================
        $pdfUrl = url('storage/'.$storagePath);
        $this->info('3️⃣  Link publik: '.$pdfUrl);

        // ===== Data caption =====
        $now = now()->locale('id');
        $sekolah = Pengaturan::first()?->nama_sekolah ?? 'SMKN 2 KOLAKA';
        $hari = strtoupper($now->isoFormat('dddd'));
        $key = env('DISPLAY_KEY', 'piket2026');
        $urlTv = url('/tampil').'?k='.$key;

        $jumlahHadir = AbsensiPetugas::where('tanggal', now()->toDateString())->count();
        $jumlahPetugas = User::whereIn('role', ['petugas', 'koordinator'])->count();
        $jumlahAlpha = max(0, $jumlahPetugas - $jumlahHadir);

        $wa = new WhatsAppService();

        // =====================================================
        // LANGKAH 4-6: Fonnte download PDF → upload ke WA
        //              Grup menerima FILE PDF BENERAN
        // =====================================================
        $this->info('4️⃣  Kirim file PDF via Fonnte (Fonnte akan download & upload ulang)...');

        $captionPdf = implode("\n", [
            '*📄 LAPORAN HARIAN TIM PIKET '.$hari.'*',
            '_'.$sekolah.'_',
            $now->isoFormat('dddd, D MMMM Y'),
        ]);

        $notifPdf = $wa->kirimPdf($grup, $pdfUrl, $filename, $captionPdf);

        if ($notifPdf->status !== 'terkirim') {
            $this->error('❌ Gagal kirim PDF: '.($notifPdf->pesan_error ?? 'unknown'));
            return Command::FAILURE;
        }
        $this->info('6️⃣  ✅ Grup WA menerima FILE PDF beneran!');

        // Jeda 3 detik biar tidak kena rate limit Fonnte
        sleep(3);

        // =====================================================
        // LANGKAH 7: Banner + link Live View
        // =====================================================
        $this->info('7️⃣  Kirim banner + link Live View...');

        $logoUrl = (Pengaturan::first()?->logo) ? route('logo.sekolah') : null;

        $captionBanner = implode("\n", [
            '*LAPORAN TIM PIKET '.$hari.'*',
            '_'.$sekolah.'_',
            $now->isoFormat('dddd, D MMMM Y'),
            '',
            '━━━━━━━━━━━━━━━━━━━━',
            '👥 Petugas Hadir : *'.$jumlahHadir.' orang*',
            '❌ Alpha         : *'.$jumlahAlpha.' orang*',
            '━━━━━━━━━━━━━━━━━━━━',
            '',
            '🔴 *Live View dashboard piket hari ini*:',
            $urlTv,
            '',
            '_© Sistem Informasi Piket_',
        ]);

        if ($logoUrl) {
            $notifBanner = $wa->sendImage($grup, $logoUrl, $captionBanner);
        } else {
            $notifBanner = $wa->kirim($grup, $captionBanner);
        }

        // ===== File TIDAK dihapus sekarang =====
        // Fonnte butuh waktu download; pembersihan via laporan:bersih-pdf (tiap malam)
        $this->info('📌 File PDF disimpan (dibersihkan otomatis tiap malam).');

        if ($notifBanner->status === 'terkirim') {
            $this->info('✅ Banner + Live View terkirim!');
            return Command::SUCCESS;
        }

        $this->error('⚠️ PDF terkirim, tapi banner gagal: '.($notifBanner->pesan_error ?? 'unknown'));
        return Command::FAILURE;
    }

    // ===== Data untuk view laporan.pdf =====
    private function generatePdfData(): array
    {
        $dari = now()->startOfDay();
        $sampai = now()->endOfDay();
        $dariStr = $dari->toDateString();
        $sampaiStr = $sampai->toDateString();
        $withSiswa = 'siswa:id,nisn,nis,nama,kelas,jurusan';

        $absensiPetugas = AbsensiPetugas::whereBetween('tanggal', [$dariStr, $sampaiStr])
            ->orderBy('tanggal')->orderBy('jam_masuk')->get();

        $rekapPetugas = User::whereIn('role', ['petugas', 'koordinator'])
            ->orderBy('name')->get()
            ->map(function ($u) use ($dariStr, $sampaiStr, $dari) {
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

        $hadirHariIni = AbsensiPetugas::where('tanggal', $sampaiStr)
            ->whereIn('status', ['tepat_waktu', 'terlambat'])->count();
        $alphaHariIni = max(0, User::whereIn('role', ['petugas', 'koordinator'])->count() - $hadirHariIni);

        $keterlambatan = Keterlambatan::with($withSiswa)->whereBetween('tanggal', [$dariStr, $sampaiStr])->orderBy('tanggal')->get();
        $izinKeluar = IzinKeluar::with($withSiswa)->whereBetween('tanggal', [$dariStr, $sampaiStr])->orderBy('tanggal')->get();
        $pelanggaran = Pelanggaran::with($withSiswa)->whereBetween('tanggal', [$dariStr, $sampaiStr])->orderBy('tanggal')->get();
        $tamu = BukuTamu::whereBetween('tanggal_kunjungan', [$dariStr, $sampaiStr])->orderBy('tanggal_kunjungan')->get();

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
            ['label' => 'Total Siswa Aktif', 'nilai' => Siswa::where('aktif', true)->count().' siswa'],
            ['label' => 'Petugas Piket Hadir', 'nilai' => $hadirHariIni.' orang'],
            ['label' => 'Petugas Alpha', 'nilai' => $alphaHariIni.' orang'],
            ['label' => 'Keterlambatan Siswa', 'nilai' => $keterlambatan->count().' kejadian'],
            ['label' => 'Izin Keluar', 'nilai' => $izinKeluar->count().' kejadian'],
            ['label' => 'Pelanggaran', 'nilai' => $pelanggaran->count().' kejadian ('.$pelanggaran->sum('poin').' poin)'],
            ['label' => 'Kunjungan Tamu', 'nilai' => $tamu->count().' kunjungan'],
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

        $koordinator = User::where('role', 'koordinator')->orderBy('name')->first();
        $tempatTanggal = ($pengaturan->kota ?? 'Kolaka').', '.now()->isoFormat('D MMMM Y');

        $totalData = $absensiPetugas->count() + $keterlambatan->count() + $izinKeluar->count()
                   + $pelanggaran->count() + $tamu->count();

        return [
            'pengaturan'       => $pengaturan,
            'logo'             => $logo,
            'logoInstansi'     => $logoInstansi,
            'labelPeriode'     => 'Harian — '.now()->isoFormat('dddd, D MMMM Y'),
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
            'tempatTanggal'    => $tempatTanggal,
        ];
    }
}
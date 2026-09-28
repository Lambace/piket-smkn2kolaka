<?php

namespace App\Http\Controllers;

use App\Models\AbsensiPetugas;
use App\Models\BukuTamu;
use App\Models\IzinKeluar;
use App\Models\Keterlambatan;
use App\Models\Pelanggaran;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Dashboard', $this->buildData($request));
    }

    public function tampil(Request $request)
    {
        $key = config('services.display.key');

        if ($key && $request->query('k') !== $key) {
            abort(403, 'Akses ditolak. Tautan tidak valid.');
        }

        // ===== 0. PARAMETER FILTER =====
        $filterHari = $request->input('filter_hari', Carbon::now('Asia/Makassar')->isoFormat('dddd'));
        $periode = $request->input('periode_tampilan', 'harian');

        // ===== 1. FILTER RENTANG TANGGAL (PANEL UNGU - untuk data siswa/tamu) =====
        $defaultDari = Carbon::now('Asia/Makassar')->startOfMonth()->toDateString();
        $defaultSampai = Carbon::now('Asia/Makassar')->toDateString();

        $dariTanggal = $request->input('dari_tanggal', $defaultDari);
        $sampaiTanggal = $request->input('sampai_tanggal', $defaultSampai);
        
        $rangeStart = Carbon::parse($dariTanggal, 'Asia/Makassar')->startOfDay();
        $rangeEnd   = Carbon::parse($sampaiTanggal, 'Asia/Makassar')->endOfDay();

        $withSiswa = 'siswa:id,nama,kelas,jurusan,nisn';

        // ===== 2. DATA LIST SISWA/TAMU (Mengikuti Panel Ungu) =====
        $keterlambatanList = Keterlambatan::with($withSiswa)
            ->whereBetween('tanggal', [$rangeStart, $rangeEnd])
            ->orderByDesc('tanggal')->orderByDesc('jam_datang')->limit(50)->get()
            ->map(fn ($k) => [
                'id' => $k->id, 'nama' => $k->siswa?->nama, 'kelas' => $k->siswa?->kelas, 
                'tanggal' => $k->tanggal->format('d/m/Y'), 'jam_datang' => $k->jam_datang, 
                'menit_terlambat' => (int) $k->menit_terlambat, 'status' => $k->status,
            ]);

        $izinKeluarList = IzinKeluar::with($withSiswa)
            ->whereBetween('tanggal', [$rangeStart, $rangeEnd])
            ->orderByDesc('tanggal')->orderByDesc('jam_keluar')->limit(50)->get()
            ->map(fn ($i) => [
                'id' => $i->id, 'nama' => $i->siswa?->nama, 'kelas' => $i->siswa?->kelas, 
                'tanggal' => $i->tanggal->format('d/m/Y'), 'jam_keluar' => $i->jam_keluar, 
                'jam_kembali' => $i->jam_kembali, 'jenis' => $i->jenis, 'status' => $i->status,
            ]);

        $pelanggaranList = Pelanggaran::with($withSiswa)
            ->whereBetween('tanggal', [$rangeStart, $rangeEnd])
            ->orderByDesc('tanggal')->orderByDesc('created_at')->limit(50)->get()
            ->map(fn ($p) => [
                'id' => $p->id, 'nama' => $p->siswa?->nama, 'kelas' => $p->siswa?->kelas, 
                'tanggal' => $p->tanggal->format('d/m/Y'), 'jenis_pelanggaran' => $p->jenis_pelanggaran, 
                'poin' => (int) $p->poin, 'status' => $p->status,
            ]);

        $bukuTamuList = BukuTamu::whereBetween('tanggal_kunjungan', [$rangeStart, $rangeEnd])
            ->orderByDesc('tanggal_kunjungan')->orderByDesc('jam_masuk')->limit(50)->get()
            ->map(fn ($t) => [
                'id' => $t->id, 'nama' => $t->nama, 'instansi' => $t->instansi, 
                'tanggal' => $t->tanggal_kunjungan->format('d/m/Y'), 'jam_masuk' => $t->jam_masuk, 
                'jam_keluar' => $t->jam_keluar, 'status' => $t->jam_keluar ? 'sudah_keluar' : 'di_sekolah',
            ]);

        // ===== 3. STATISTIK (Mengikuti Panel Ungu) =====
        $stats = [
            'total_siswa' => Siswa::where('aktif', true)->count(),
            'terlambat'   => Keterlambatan::whereBetween('tanggal', [$rangeStart, $rangeEnd])->count(),
            'izin_keluar' => IzinKeluar::whereBetween('tanggal', [$rangeStart, $rangeEnd])->count(),
            'pelanggaran' => Pelanggaran::whereBetween('tanggal', [$rangeStart, $rangeEnd])->count(),
            'tamu'        => BukuTamu::whereBetween('tanggal_kunjungan', [$rangeStart, $rangeEnd])->count(),
        ];

        // ===== 4. ABSENSI PETUGAS (LOGIKA CERDAS SESUAI ATURAN 1-6) =====
        $hariIniStr = Carbon::today('Asia/Makassar')->toDateString();
        $namaHariIni = Carbon::now('Asia/Makassar')->isoFormat('dddd');
        $jamSekarang = Carbon::now('Asia/Makassar')->format('H:i');
        $batasAlpha = '07:30';

        // Tentukan rentang tanggal berdasarkan periode
        if ($periode === 'harian') {
            $absensiRangeStart = $hariIniStr;
            $absensiRangeEnd = $hariIniStr;
        } elseif ($periode === 'bulanan') {
            $absensiRangeStart = Carbon::now('Asia/Makassar')->startOfMonth()->toDateString();
            $absensiRangeEnd = Carbon::now('Asia/Makassar')->toDateString();
        } elseif ($periode === 'semester') {
            $bulan = Carbon::now()->month;
            $tahun = Carbon::now()->year;
            $absensiRangeStart = ($bulan <= 6) ? "$tahun-01-01" : "$tahun-07-01";
            $absensiRangeEnd = Carbon::now('Asia/Makassar')->toDateString();
        } else {
            $absensiRangeStart = $hariIniStr;
            $absensiRangeEnd = $hariIniStr;
        }

        $absensiPetugas = collect();

        // ATURAN 1: Default View (Filter == Hari Ini)
        if ($filterHari === $namaHariIni) {
            // Cek Libur (Minggu atau Weekday tapi tidak ada data sama sekali)
            $totalAbsensiHariIni = AbsensiPetugas::whereDate('tanggal', $hariIniStr)->count();
            $isLibur = ($namaHariIni === 'Minggu') || ($totalAbsensiHariIni === 0);

            if ($isLibur) {
                $absensiPetugas = collect([['status' => 'libur', 'pesan' => 'Hari ini sekolah libur']]);
            }
        }

        // Jika tidak libur, lanjutkan proses
        if ($absensiPetugas->isEmpty()) {
            // Cari Petugas yang dijadwalkan pada $filterHari
            $petugasTerjadwal = User::whereIn('role', ['petugas', 'koordinator'])
                ->where('hari_piket', $filterHari)
                ->orderBy('name')->get();

            // ATURAN 2: Mismatch (Tidak ada petugas dijadwalkan)
            if ($petugasTerjadwal->isEmpty()) {
                $absensiPetugas = collect([['status' => 'tidak_ada_jadwal', 'pesan' => "Tidak ada petugas yang dijadwalkan untuk hari $filterHari"]]);
            } else {
                // Ambil Data Absensi dalam Rentang
                $absensiData = AbsensiPetugas::whereBetween('tanggal', [$absensiRangeStart, $absensiRangeEnd])->get();

                if ($periode === 'harian') {
                    // ATURAN 3: Tampilan Harian (List Detail)
                    $hadirList = collect();
                    $alphaList = collect();
                    $belumAbsenList = collect();

                    foreach ($petugasTerjadwal as $p) {
                        $namaKey = strtolower(trim($p->name));
                        $absen = $absensiData->firstWhere(fn($a) => strtolower(trim($a->nama)) === $namaKey);
                        $jabatan = $p->role === 'koordinator' ? 'Koordinator Piket' : 'Guru Piket';

                        if ($absen) {
                            if ($absen->status === 'alpha') {
                                $alphaList->push(['nama' => $p->name, 'jabatan' => $jabatan, 'jam' => null, 'status' => 'alpha']);
                            } else {
                                $hadirList->push(['nama' => $p->name, 'jabatan' => $jabatan, 'jam' => $absen->jam_masuk ? substr($absen->jam_masuk, 0, 5) : null, 'status' => $absen->status]);
                            }
                        } else {
                            // Jika filter hari != hari ini, anggap belum absen
                            // Jika filter hari == hari ini, cek jam untuk alpha
                            if ($filterHari === $namaHariIni && $jamSekarang >= $batasAlpha) {
                                $alphaList->push(['nama' => $p->name, 'jabatan' => $jabatan, 'jam' => null, 'status' => 'alpha']);
                            } else {
                                $belumAbsenList->push(['nama' => $p->name, 'jabatan' => $jabatan, 'jam' => null, 'status' => 'belum_absen']);
                            }
                        }
                    }
                    $absensiPetugas = $hadirList->merge($belumAbsenList)->merge($alphaList)->values();

                } else {
                    // ATURAN 5 & 6: Tampilan Bulanan/Semester (Rekapitulasi)
                    $rekapList = collect();

                    foreach ($petugasTerjadwal as $p) {
                        $namaKey = strtolower(trim($p->name));
                        $absensiUser = $absensiData->filter(fn($a) => strtolower(trim($a->nama)) === $namaKey);

                        // Hitung Status
                        $h = $absensiUser->filter(fn($a) => in_array($a->status, ['tepat_waktu', 'terlambat']))->count();
                        $a_db = $absensiUser->filter(fn($a) => $a->status === 'alpha')->count();
                        $i = $absensiUser->filter(fn($a) => $a->status === 'izin')->count();
                        $s = $absensiUser->filter(fn($a) => $a->status === 'sakit')->count();
                        $dl = $absensiUser->filter(fn($a) => $a->status === 'dl')->count();

                        // Hitung Ekspektasi (Jumlah hari $filterHari dalam rentang)
                        $ekspektasi = 0;
                        $cursor = Carbon::parse($absensiRangeStart);
                        $endCursor = Carbon::parse($absensiRangeEnd);
                        while ($cursor->lte($endCursor)) {
                            if ($cursor->isoFormat('dddd') === $filterHari) {
                                $ekspektasi++;
                            }
                            $cursor->addDay();
                        }

                        // Alpha = Ekspektasi - (Hadir + Izin + Sakit + DL)
                        $a_final = max($a_db, $ekspektasi - ($h + $i + $s + $dl));

                        if ($h > 0 || $a_final > 0 || $i > 0 || $s > 0 || $dl > 0) {
                            $rekapList->push([
                                'nama' => $p->name,
                                'jabatan' => $p->role === 'koordinator' ? 'Koordinator Piket' : 'Guru Piket',
                                'h' => $h,
                                'a' => $a_final,
                                'i' => $i,
                                's' => $s,
                                'dl' => $dl,
                                'status' => 'rekap'
                            ]);
                        }
                    }
                    $absensiPetugas = $rekapList;
                }
            }
        }

        // Debug Log
        logger("=== DEBUG TAMPIL ===");
        logger("Hari: {$namaHariIni} | Filter: {$filterHari} | Periode: {$periode} | Jam: {$jamSekarang}");
        logger("Status Card Petugas: " . ($absensiPetugas->first()['status'] ?? 'DATA'));

        // ===== 5. GRAFIK & CHART (Mengikuti Panel Ungu) =====
        $chartData = Keterlambatan::select('siswa.kelas as label', DB::raw('COUNT(*) as jumlah'))
            ->join('siswa', 'siswa.id', '=', 'keterlambatan.siswa_id')
            ->whereBetween('keterlambatan.tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa.kelas')->orderByDesc('jumlah')->get()
            ->map(fn ($d) => ['label' => $d->label, 'jumlah' => (int) $d->jumlah])->values();

        $donutJurusan = Keterlambatan::select('siswa.jurusan as label', DB::raw('COUNT(*) as jumlah'))
            ->join('siswa', 'siswa.id', '=', 'keterlambatan.siswa_id')
            ->whereBetween('keterlambatan.tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa.jurusan')->orderByDesc('jumlah')->get()
            ->map(fn ($d) => ['label' => $d->label ?: 'Tanpa Jurusan', 'jumlah' => (int) $d->jumlah]);

        $chartIzinKelas = IzinKeluar::select('siswa.kelas as label', DB::raw('COUNT(*) as jumlah'))
            ->join('siswa', 'siswa.id', '=', 'izin_keluar.siswa_id')
            ->whereBetween('izin_keluar.tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa.kelas')->orderByDesc('jumlah')->get()
            ->map(fn ($d) => ['label' => $d->label ?? 'Tanpa Kelas', 'jumlah' => (int) $d->jumlah])->values();

        $donutIzinJurusan = IzinKeluar::select('siswa.jurusan as label', DB::raw('COUNT(*) as jumlah'))
            ->join('siswa', 'siswa.id', '=', 'izin_keluar.siswa_id')
            ->whereBetween('izin_keluar.tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa.jurusan')->orderByDesc('jumlah')->get()
            ->map(fn ($d) => ['label' => $d->label ?: 'Tanpa Jurusan', 'jumlah' => (int) $d->jumlah]);

        $chartPelanggaranKelas = Pelanggaran::select('siswa.kelas as label', DB::raw('COUNT(*) as jumlah'))
            ->join('siswa', 'siswa.id', '=', 'pelanggaran.siswa_id')
            ->whereBetween('pelanggaran.tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa.kelas')->orderByDesc('jumlah')->get()
            ->map(fn ($d) => ['label' => $d->label ?? 'Tanpa Kelas', 'jumlah' => (int) $d->jumlah])->values();

        $donutPelanggaranJurusan = Pelanggaran::select('siswa.jurusan as label', DB::raw('COUNT(*) as jumlah'))
            ->join('siswa', 'siswa.id', '=', 'pelanggaran.siswa_id')
            ->whereBetween('pelanggaran.tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa.jurusan')->orderByDesc('jumlah')->get()
            ->map(fn ($d) => ['label' => $d->label ?: 'Tanpa Jurusan', 'jumlah' => (int) $d->jumlah]);

        // ===== 6. TABEL RANKING & AKTIVITAS (Mengikuti Panel Ungu) =====
        $topTerlambat = Keterlambatan::select('siswa_id', DB::raw('COUNT(*) as jumlah'), DB::raw('AVG(menit_terlambat) as rata_menit'))
            ->with('siswa:id,nisn,nama,kelas')->whereBetween('tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa_id')->orderByDesc('jumlah')->limit(5)->get()
            ->map(fn ($k) => ['nisn' => $k->siswa?->nisn, 'nama' => $k->siswa?->nama, 'kelas' => $k->siswa?->kelas, 'jumlah' => (int) $k->jumlah, 'rata_menit' => round((float) $k->rata_menit, 1)]);

        $topPelanggaran = Pelanggaran::select('siswa_id', DB::raw('SUM(poin) as total_poin'), DB::raw('COUNT(*) as jumlah_kasus'))
            ->with('siswa:id,nisn,nama,kelas')->whereBetween('tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa_id')->orderByDesc('total_poin')->limit(5)->get()
            ->map(fn ($p) => ['nisn' => $p->siswa?->nisn, 'nama' => $p->siswa?->nama, 'kelas' => $p->siswa?->kelas, 'total_poin' => (int) $p->total_poin, 'jumlah_kasus' => (int) $p->jumlah_kasus]);

        $aktivitas = collect();
        Keterlambatan::with('siswa:id,nama,kelas')->whereBetween('tanggal', [$rangeStart, $rangeEnd])->latest()->take(3)->get()->each(fn ($k) => $aktivitas->push(['waktu' => $k->created_at?->toIsoString(), 'tipe' => 'Terlambat', 'warna' => 'red', 'teks' => ($k->siswa?->nama ?? '-').' terlambat '.($k->menit_terlambat ?? 0).' menit']));
        Pelanggaran::with('siswa:id,nama,kelas')->whereBetween('tanggal', [$rangeStart, $rangeEnd])->latest()->take(3)->get()->each(fn ($p) => $aktivitas->push(['waktu' => $p->created_at?->toIsoString(), 'tipe' => 'Pelanggaran', 'warna' => 'orange', 'teks' => ($p->siswa?->nama ?? '-').' - '.($p->jenis_pelanggaran ?? '-')]));
        $aktivitas = $aktivitas->sortByDesc('waktu')->take(8)->values();

        $pengaturan = Pengaturan::first();

        // ===== 7. KIRIM KE FRONTEND =====
        return Inertia::render('Tampil', [
            'hariIni'             => Carbon::now('Asia/Makassar')->isoFormat('dddd, D MMMM Y'),
            'stats'               => $stats,
            'absensiPetugas'      => $absensiPetugas,
            'displayKey'          => $key,
            'keterlambatanList'   => $keterlambatanList,
            'izinKeluarList'      => $izinKeluarList,
            'pelanggaranList'     => $pelanggaranList,
            'bukuTamuList'        => $bukuTamuList,
            'chartData'           => $chartData, 
            'donutJurusan'        => $donutJurusan,
            'chartIzinKelas'      => $chartIzinKelas,
            'donutIzinJurusan'    => $donutIzinJurusan,
            'chartPelanggaranKelas'     => $chartPelanggaranKelas,
            'donutPelanggaranJurusan'   => $donutPelanggaranJurusan,
            'topTerlambat'        => $topTerlambat,
            'topPelanggaran'      => $topPelanggaran, 
            'aktivitas'           => $aktivitas,
            'pengaturan' => $pengaturan ? [
                'nama_sekolah' => $pengaturan->nama_sekolah,
                'logo_url'     => $pengaturan->logo ? Storage::url($pengaturan->logo) : null,
                'logo'         => $pengaturan->logo,
            ] : null,
            'currentFilters' => [
                'dari_tanggal'   => $dariTanggal,
                'sampai_tanggal' => $sampaiTanggal,
            ],
        ]);
    }

    private function buildData(Request $request): array
    {
        $dariTanggal = $request->input('dari_tanggal', Carbon::today()->startOfMonth()->toDateString());
        $sampaiTanggal = $request->input('sampai_tanggal', Carbon::today()->toDateString());

        $rangeStart = Carbon::parse($dariTanggal)->startOfDay();
        $rangeEnd   = Carbon::parse($sampaiTanggal)->endOfDay();

        $periodeGrafik = $request->input('periode_grafik', '7');
        $hariGrafik = in_array($periodeGrafik, ['7', '14', '30']) ? (int) $periodeGrafik : 7;

        $grafikKelas = $request->input('grafik_kelas');
        $grafikJurusan = $request->input('grafik_jurusan');

        $stats = [
            'total_siswa' => Siswa::where('aktif', true)->count(),
            'terlambat'   => Keterlambatan::whereBetween('tanggal', [$rangeStart, $rangeEnd])->count(),
            'izin_keluar' => IzinKeluar::whereBetween('tanggal', [$rangeStart, $rangeEnd])->count(),
            'pelanggaran' => Pelanggaran::whereBetween('tanggal', [$rangeStart, $rangeEnd])->count(),
            'tamu'        => BukuTamu::whereBetween('tanggal_kunjungan', [$rangeStart, $rangeEnd])->count(),
            'tamu_masih_di_sekolah' => BukuTamu::whereDate('tanggal_kunjungan', Carbon::today())->whereNull('jam_keluar')->count(),
        ];

        $chartData = Keterlambatan::select('siswa.kelas as label', DB::raw('COUNT(*) as jumlah'))
            ->join('siswa', 'siswa.id', '=', 'keterlambatan.siswa_id')
            ->whereBetween('keterlambatan.tanggal', [$rangeStart, $rangeEnd])
            ->when($grafikKelas, fn ($q) => $q->where('siswa.kelas', $grafikKelas))
            ->when($grafikJurusan, fn ($q) => $q->where('siswa.jurusan', $grafikJurusan))
            ->groupBy('siswa.kelas')->orderByDesc('jumlah')->get()
            ->map(fn ($d) => ['label' => $d->label, 'jumlah' => (int) $d->jumlah])->values();

        $chartPelanggaran = [];
        for ($i = $hariGrafik - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $jumlahHariIni = Pelanggaran::whereBetween('tanggal', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])->count();
            $chartPelanggaran[] = [
                'label'  => $hariGrafik > 14 ? $date->isoFormat('D/M') : $date->isoFormat('ddd'),
                'title'  => $date->isoFormat('ddd, D MMM'),
                'jumlah' => $jumlahHariIni,
            ];
        }

        $donutJurusan = Keterlambatan::select('siswa.jurusan as label', DB::raw('COUNT(*) as jumlah'))
            ->join('siswa', 'siswa.id', '=', 'keterlambatan.siswa_id')
            ->whereBetween('keterlambatan.tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa.jurusan')->orderByDesc('jumlah')->get()
            ->map(fn ($d) => ['label' => $d->label ?: 'Tanpa Jurusan', 'jumlah' => (int) $d->jumlah]);

        $donutStatusPelanggaran = Pelanggaran::select('status as label', DB::raw('COUNT(*) as jumlah'))
            ->whereBetween('tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('status')->orderByDesc('jumlah')->get()
            ->map(fn ($d) => ['label' => ucfirst($d->label ?? 'Tanpa Status'), 'jumlah' => (int) $d->jumlah]);

        $jenisPelanggaran = Pelanggaran::select('jenis_pelanggaran', DB::raw('COUNT(*) as jumlah'))
            ->whereBetween('tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('jenis_pelanggaran')->orderByDesc('jumlah')->limit(5)->get()
            ->map(fn ($d) => ['label' => $d->jenis_pelanggaran, 'jumlah' => (int) $d->jumlah]);

        $topPelanggaran = Pelanggaran::select('siswa_id', DB::raw('SUM(poin) as total_poin'), DB::raw('COUNT(*) as jumlah_kasus'))
            ->with('siswa:id,nisn,nama,kelas')->whereBetween('tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa_id')->orderByDesc('total_poin')->limit(5)->get()
            ->map(fn ($p) => [
                'nisn' => $p->siswa?->nisn ?? '-', 'nama' => $p->siswa?->nama ?? '-', 'kelas' => $p->siswa?->kelas ?? '-',
                'total_poin' => (int) ($p->total_poin ?? 0), 'jumlah_kasus' => (int) ($p->jumlah_kasus ?? 0),
            ]);

        $topTerlambat = Keterlambatan::select('siswa_id', DB::raw('COUNT(*) as jumlah'), DB::raw('AVG(menit_terlambat) as rata_menit'))
            ->with('siswa:id,nisn,nama,kelas')->whereBetween('tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa_id')->orderByDesc('jumlah')->limit(5)->get()
            ->map(fn ($k) => [
                'nisn' => $k->siswa?->nisn ?? '-', 'nama' => $k->siswa?->nama ?? '-', 'kelas' => $k->siswa?->kelas ?? '-',
                'jumlah' => (int) ($k->jumlah ?? 0), 'rata_menit' => round((float) ($k->rata_menit ?? 0), 1),
            ]);

        $kelasMelanggarTertinggi = Pelanggaran::select('siswa.kelas as kelas', DB::raw('COUNT(*) as jumlah'), DB::raw('SUM(pelanggaran.poin) as total_poin'))
            ->join('siswa', 'siswa.id', '=', 'pelanggaran.siswa_id')
            ->whereBetween('pelanggaran.tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa.kelas')->orderByDesc('jumlah')->limit(5)->get()
            ->map(fn ($k) => ['kelas' => $k->kelas ?? 'Tanpa Kelas', 'jumlah' => (int) $k->jumlah, 'total_poin' => (int) ($k->total_poin ?? 0)]);

        $tanggalTarget = $rangeStart->toDateString(); 
        $keterlambatanHarian = Keterlambatan::with('siswa:id,nama,kelas,nisn')
            ->whereDate('tanggal', $tanggalTarget)->orderByDesc('jam_datang')->get()
            ->map(fn ($k) => [
                'id' => $k->id, 'nama' => $k->siswa?->nama ?? '-', 'kelas' => $k->siswa?->kelas ?? '-',
                'nisn' => $k->siswa?->nisn ?? '-', 'tanggal' => $k->tanggal, 'jam_datang' => $k->jam_datang,
                'menit_terlambat' => (int) $k->menit_terlambat, 'status' => $k->status,
            ]);

        $aktivitas = collect();
        Keterlambatan::with('siswa:id,nama,kelas')->latest()->take(5)->get()->each(fn ($k) => $aktivitas->push(['waktu' => $k->created_at?->toIsoString(), 'tipe' => 'Terlambat', 'warna' => 'red', 'teks' => ($k->siswa?->nama ?? '-').' ('.($k->siswa?->kelas ?? '-').') terlambat '.($k->menit_terlambat ?? 0).' menit']));
        IzinKeluar::with('siswa:id,nama,kelas')->latest()->take(5)->get()->each(fn ($i) => $aktivitas->push(['waktu' => $i->created_at?->toIsoString(), 'tipe' => 'Izin Keluar', 'warna' => 'yellow', 'teks' => ($i->siswa?->nama ?? '-').' ('.($i->siswa?->kelas ?? '-').') izin keluar: '.($i->jenis ?? '-')]));
        Pelanggaran::with('siswa:id,nama,kelas')->latest()->take(5)->get()->each(fn ($p) => $aktivitas->push(['waktu' => $p->created_at?->toIsoString(), 'tipe' => 'Pelanggaran', 'warna' => 'orange', 'teks' => ($p->siswa?->nama ?? '-').' ('.($p->siswa?->kelas ?? '-').') '.($p->jenis_pelanggaran ?? '-').' ('.($p->poin ?? 0).' poin)']));
        BukuTamu::latest()->take(5)->get()->each(fn ($t) => $aktivitas->push(['waktu' => $t->created_at?->toIsoString(), 'tipe' => 'Tamu', 'warna' => 'blue', 'teks' => $t->nama.' ('.($t->instansi ?: 'Umum').') — '.($t->keperluan ?? '-')]));
        $aktivitas = $aktivitas->sortByDesc('waktu')->take(8)->values();

        $kelasOptions   = Siswa::distinct()->orderBy('kelas')->pluck('kelas')->filter()->values();
        $jurusanOptions = Siswa::distinct()->orderBy('jurusan')->pluck('jurusan')->filter()->values();
        $rentangAktif = Carbon::parse($dariTanggal)->isoFormat('D MMM Y') .' — '.Carbon::parse($sampaiTanggal)->isoFormat('D MMM Y');
        $key = config('services.display.key');
        $tampilUrl = route('tampil', $key ? ['k' => $key] : []);

        // ===== ABSENSI PETUGAS UNTUK DASHBOARD (HARI INI) =====
        $hariIniStr = Carbon::today('Asia/Makassar')->toDateString();
        $namaHariIni = Carbon::now('Asia/Makassar')->isoFormat('dddd');
        $jamSekarang = Carbon::now('Asia/Makassar')->format('H:i');
        $batasAlpha = '07:30';

        $petugasHariIni = User::whereIn('role', ['petugas', 'koordinator'])
            ->where(function ($q) use ($namaHariIni) {
                $q->where('hari_piket', $namaHariIni)
                  ->orWhere('role', 'koordinator');
            })
            ->orderBy('name')
            ->get();

        $absensiHariIni = AbsensiPetugas::whereDate('tanggal', $hariIniStr)->get();
        $absensiMap = [];
        foreach ($absensiHariIni as $absen) {
            $absensiMap[strtolower(trim($absen->nama))] = $absen;
        }

        $hadirList = collect();
        $alphaList = collect();
        $belumAbsenList = collect();

        foreach ($petugasHariIni as $petugas) {
            $namaKey = strtolower(trim($petugas->name));
            $jabatan = $petugas->role === 'koordinator' ? 'Koordinator Piket' : 'Guru Piket';

            if (isset($absensiMap[$namaKey])) {
                if ($absensiMap[$namaKey]->status === 'alpha') {
                    $alphaList->push(['nama' => $petugas->name, 'jabatan' => $jabatan, 'jam' => null, 'status' => 'alpha']);
                } else {
                    $hadirList->push(['nama' => $petugas->name, 'jabatan' => $jabatan, 'jam' => $absensiMap[$namaKey]->jam_masuk ? substr($absensiMap[$namaKey]->jam_masuk, 0, 5) : null, 'status' => $absensiMap[$namaKey]->status]);
                }
            } else {
                if ($jamSekarang >= $batasAlpha) {
                    $alphaList->push(['nama' => $petugas->name, 'jabatan' => $jabatan, 'jam' => null, 'status' => 'alpha']);
                } else {
                    $belumAbsenList->push(['nama' => $petugas->name, 'jabatan' => $jabatan, 'jam' => null, 'status' => 'belum_absen']);
                }
            }
        }

        $absensiPetugasDashboard = $hadirList->merge($belumAbsenList)->merge($alphaList)->values();

        return [
            'stats'                   => $stats,
            'absensiPetugas'          => $absensiPetugasDashboard,
            'keterlambatanHarian'     => $keterlambatanHarian, 
            'chartData'               => $chartData,
            'chartPelanggaran'        => $chartPelanggaran,
            'donutJurusan'            => $donutJurusan,
            'donutStatusPelanggaran'  => $donutStatusPelanggaran,
            'jenisPelanggaran'        => $jenisPelanggaran,
            'kelasMelanggarTertinggi' => $kelasMelanggarTertinggi,
            'topPelanggaran'          => $topPelanggaran,
            'topTerlambat'            => $topTerlambat,
            'aktivitas'               => $aktivitas,
            'kelasOptions'            => $kelasOptions,
            'jurusanOptions'          => $jurusanOptions,
            'rentangAktif'            => $rentangAktif,
            'hariIni'                 => Carbon::today()->isoFormat('dddd, D MMMM Y'),
            'tampilUrl'               => $tampilUrl,
            'params'                  => [
                'dari_tanggal'   => $dariTanggal,
                'sampai_tanggal' => $sampaiTanggal,
                'periode_grafik' => (string) $periodeGrafik,
                'grafik_kelas'   => $grafikKelas,
                'grafik_jurusan' => $grafikJurusan,
            ],
        ];
    }
}
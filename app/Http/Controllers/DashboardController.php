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
        $periodeTampilan = $request->input('periode_tampilan', 'harian');
        $filterHari = $request->input('filter_hari', 'Semua Hari');

        // ===== 1. FILTER RENTANG TANGGAL =====
        $dariTanggal = $request->input('dari_tanggal', Carbon::today('Asia/Makassar')->toDateString());
        $sampaiTanggal = $request->input('sampai_tanggal', Carbon::today('Asia/Makassar')->toDateString());
        
        $rangeStart = Carbon::parse($dariTanggal, 'Asia/Makassar')->startOfDay();
        $rangeEnd   = Carbon::parse($sampaiTanggal, 'Asia/Makassar')->endOfDay();

        $withSiswa = 'siswa:id,nama,kelas,jurusan,nisn';

        // ===== FUNGSI HELPER: FILTER BERDASARKAN HARI (DAYOFWEEK) =====
        $applyDayFilter = function ($query, $columnName = 'tanggal') use ($filterHari) {
            if ($filterHari !== 'Semua Hari') {
                $dayMap = [
                    'Minggu' => 1, 'Senin' => 2, 'Selasa' => 3, 'Rabu' => 4, 
                    'Kamis' => 5, 'Jumat' => 6, 'Sabtu' => 7
                ];
                $dayNum = $dayMap[$filterHari] ?? null;
                if ($dayNum) {
                    $query->whereRaw("DAYOFWEEK({$columnName}) = ?", [$dayNum]);
                }
            }
            return $query;
        };

        // ===== 2. DATA LIST (Diterapkan Filter Hari) =====
        $keterlambatanQuery = Keterlambatan::with($withSiswa)->whereBetween('tanggal', [$rangeStart, $rangeEnd]);
        $applyDayFilter($keterlambatanQuery, 'tanggal');
        $keterlambatanList = $keterlambatanQuery->orderByDesc('tanggal')->orderByDesc('jam_datang')->limit(50)->get()
            ->map(fn ($k) => [
                'id' => $k->id, 'nama' => $k->siswa?->nama, 'kelas' => $k->siswa?->kelas, 
                'tanggal' => $k->tanggal->format('d/m/Y'), 'jam_datang' => $k->jam_datang, 
                'menit_terlambat' => (int) $k->menit_terlambat, 'status' => $k->status,
            ]);

        $izinQuery = IzinKeluar::with($withSiswa)->whereBetween('tanggal', [$rangeStart, $rangeEnd]);
        $applyDayFilter($izinQuery, 'tanggal');
        $izinKeluarList = $izinQuery->orderByDesc('tanggal')->orderByDesc('jam_keluar')->limit(50)->get()
            ->map(fn ($i) => [
                'id' => $i->id, 'nama' => $i->siswa?->nama, 'kelas' => $i->siswa?->kelas, 
                'tanggal' => $i->tanggal->format('d/m/Y'), 'jam_keluar' => $i->jam_keluar, 
                'jam_kembali' => $i->jam_kembali, 'jenis' => $i->jenis, 'status' => $i->status,
            ]);

        $pelanggaranQuery = Pelanggaran::with($withSiswa)->whereBetween('tanggal', [$rangeStart, $rangeEnd]);
        $applyDayFilter($pelanggaranQuery, 'tanggal');
        $pelanggaranList = $pelanggaranQuery->orderByDesc('tanggal')->orderByDesc('created_at')->limit(50)->get()
            ->map(fn ($p) => [
                'id' => $p->id, 'nama' => $p->siswa?->nama, 'kelas' => $p->siswa?->kelas, 
                'tanggal' => $p->tanggal->format('d/m/Y'), 'jenis_pelanggaran' => $p->jenis_pelanggaran, 
                'poin' => (int) $p->poin, 'status' => $p->status,
            ]);

        $tamuQuery = BukuTamu::whereBetween('tanggal_kunjungan', [$rangeStart, $rangeEnd]);
        $applyDayFilter($tamuQuery, 'tanggal_kunjungan');
        $bukuTamuList = $tamuQuery->orderByDesc('tanggal_kunjungan')->orderByDesc('jam_masuk')->limit(50)->get()
            ->map(fn ($t) => [
                'id' => $t->id, 'nama' => $t->nama, 'instansi' => $t->instansi, 
                'tanggal' => $t->tanggal_kunjungan->format('d/m/Y'), 'jam_masuk' => $t->jam_masuk, 
                'jam_keluar' => $t->jam_keluar, 'status' => $t->jam_keluar ? 'sudah_keluar' : 'di_sekolah',
            ]);

        // ===== 3. STATISTIK =====
        $statsTerlambat = Keterlambatan::whereBetween('tanggal', [$rangeStart, $rangeEnd]);
        $applyDayFilter($statsTerlambat, 'tanggal');

        $statsIzin = IzinKeluar::whereBetween('tanggal', [$rangeStart, $rangeEnd]);
        $applyDayFilter($statsIzin, 'tanggal');

        $statsPelanggaran = Pelanggaran::whereBetween('tanggal', [$rangeStart, $rangeEnd]);
        $applyDayFilter($statsPelanggaran, 'tanggal');

        $statsTamu = BukuTamu::whereBetween('tanggal_kunjungan', [$rangeStart, $rangeEnd]);
        $applyDayFilter($statsTamu, 'tanggal_kunjungan');

        $stats = [
            'total_siswa' => Siswa::where('aktif', true)->count(),
            'terlambat'   => $statsTerlambat->count(),
            'izin_keluar' => $statsIzin->count(),
            'pelanggaran' => $statsPelanggaran->count(),
            'tamu'        => $statsTamu->count(),
        ];

        // ===== 4. ABSENSI PETUGAS =====
        $hariIniStr = Carbon::today('Asia/Makassar')->toDateString();
        $namaHariIni = Carbon::now('Asia/Makassar')->isoFormat('dddd');
        $jamSekarang = Carbon::now('Asia/Makassar')->format('H:i');
        $batasAlpha = '07:30';

        // Logika Libur Otomatis hanya untuk periode harian
        $totalAbsensiHariIni = AbsensiPetugas::whereDate('tanggal', $hariIniStr)->count();
        $isLiburOtomatis = ($periodeTampilan === 'harian' && $filterHari === 'Semua Hari' && $jamSekarang >= $batasAlpha && $totalAbsensiHariIni === 0);

        if ($isLiburOtomatis) {
            $absensiPetugas = collect([
                ['status' => 'libur_otomatis', 'pesan' => 'Tidak ada aktivitas piket hari ini (Dianggap Libur)']
            ]);
        } else {
            $semuaPetugas = User::whereIn('role', ['petugas', 'koordinator'])->orderBy('name')->get();
            
            // ===== PERBAIKAN: Ambil absensi yang sesuai filter hari =====
            $absensiRentangQuery = AbsensiPetugas::whereBetween('tanggal', [$rangeStart, $rangeEnd]);
            $applyDayFilter($absensiRentangQuery, 'tanggal');
            $absensiRentang = $absensiRentangQuery->get();
            
            $namaDenganAbsensi = $absensiRentang->pluck('nama')
                ->map(fn($n) => strtolower(trim($n)))
                ->unique()
                ->toArray();

            // ===== PERBAIKAN: Filter petugas berdasarkan hari_piket =====
            $petugasRelevan = $semuaPetugas->filter(function ($p) use ($filterHari, $namaDenganAbsensi, $namaHariIni) {
                $namaKey = strtolower(trim($p->name));
                
                // Untuk "Semua Hari", tampilkan yang jadwalnya hari ini
                // Untuk hari spesifik, tampilkan yang jadwalnya hari itu
                if ($filterHari === 'Semua Hari') {
                    return $p->hari_piket === $namaHariIni;
                } else {
                    return $p->hari_piket === $filterHari;
                }
            });

            // ===== PERBAIKAN: Hitung absensi per petugas berdasarkan filter hari =====
            $absensiMap = [];
            foreach ($absensiRentang as $a) {
                $namaKey = strtolower(trim($a->nama));
                if (!isset($absensiMap[$namaKey])) {
                    $absensiMap[$namaKey] = [];
                }
                $absensiMap[$namaKey][] = $a;
            }

            $hadirList = collect();
            $alphaList = collect();
            $belumAbsenList = collect();

            foreach ($petugasRelevan as $p) {
                $namaKey = strtolower(trim($p->name));
                $jabatan = $p->role === 'koordinator' ? 'Koordinator Piket' : 'Guru Piket';
                
                $absensiPetugasIni = $absensiMap[$namaKey] ?? [];

                // Untuk periode harian
                if ($periodeTampilan === 'harian') {
                    $absenHariIni = collect($absensiPetugasIni)->firstWhere('tanggal', $hariIniStr);
                    
                    if ($absenHariIni) {
                        if ($absenHariIni->status === 'alpha') {
                            $alphaList->push(['nama' => $p->name, 'jabatan' => $jabatan, 'jam' => null, 'status' => 'alpha']);
                        } else {
                            $hadirList->push(['nama' => $p->name, 'jabatan' => $jabatan, 'jam' => $absenHariIni->jam_masuk ? substr($absenHariIni->jam_masuk, 0, 5) : null, 'status' => $absenHariIni->status]);
                        }
                    } else {
                        if ($jamSekarang >= $batasAlpha) {
                            $alphaList->push(['nama' => $p->name, 'jabatan' => $jabatan, 'jam' => null, 'status' => 'alpha']);
                        } else {
                            $belumAbsenList->push(['nama' => $p->name, 'jabatan' => $jabatan, 'jam' => null, 'status' => 'belum_absen']);
                        }
                    }
                } 
                // Untuk periode mingguan/bulanan/semester
                else {
                    // Hitung jumlah kehadiran di hari yang difilter
                    $jumlahHadir = collect($absensiPetugasIni)->filter(function ($a) {
                        return in_array($a->status, ['tepat_waktu', 'terlambat']);
                    })->count();
                    
                    $jumlahAlpha = collect($absensiPetugasIni)->filter(function ($a) {
                        return $a->status === 'alpha';
                    })->count();
                    
                    $jumlahIzin = collect($absensiPetugasIni)->filter(function ($a) {
                        return $a->status === 'izin';
                    })->count();
                    
                    $jumlahSakit = collect($absensiPetugasIni)->filter(function ($a) {
                        return $a->status === 'sakit';
                    })->count();
                    
                    $jumlahDL = collect($absensiPetugasIni)->filter(function ($a) {
                        return $a->status === 'dl';
                    })->count();

                    // Hitung ekspektasi: jumlah hari yang sesuai filter dalam rentang
                    $ekspektasi = 0;
                    $cursor = $rangeStart->copy();
                    $sekarang = Carbon::now('Asia/Makassar')->toDateString();
                    
                    while ($cursor->lte($rangeEnd)) {
                        $hariCursor = $cursor->isoFormat('dddd');
                        $cocokHari = ($filterHari === 'Semua Hari') ? ($hariCursor === $namaHariIni) : ($hariCursor === $filterHari);
                        
                        if ($cocokHari && $cursor->toDateString() <= $sekarang) {
                            $ekspektasi++;
                        }
                        $cursor->addDay();
                    }

                    // Hitung alpha = ekspektasi - (hadir + izin + sakit + dl)
                    $alphaDihitung = max(0, $ekspektasi - ($jumlahHadir + $jumlahIzin + $jumlahSakit + $jumlahDL));

                    // Tampilkan sebagai rekap
                    if ($jumlahHadir > 0 || $jumlahIzin > 0 || $jumlahSakit > 0 || $jumlahDL > 0 || $alphaDihitung > 0) {
                        // Tampilkan di card yang sesuai dengan status terbanyak
                        if ($alphaDihitung > 0 && $jumlahHadir === 0) {
                            $alphaList->push([
                                'nama' => $p->name, 
                                'jabatan' => $jabatan, 
                                'jam' => null, 
                                'status' => 'alpha',
                                'rekap' => [
                                    'h' => $jumlahHadir,
                                    'a' => $alphaDihitung,
                                    'i' => $jumlahIzin,
                                    's' => $jumlahSakit,
                                    'dl' => $jumlahDL,
                                    'ekspektasi' => $ekspektasi
                                ]
                            ]);
                        } else {
                            $hadirList->push([
                                'nama' => $p->name, 
                                'jabatan' => $jabatan, 
                                'jam' => null, 
                                'status' => 'tepat_waktu',
                                'rekap' => [
                                    'h' => $jumlahHadir,
                                    'a' => $alphaDihitung,
                                    'i' => $jumlahIzin,
                                    's' => $jumlahSakit,
                                    'dl' => $jumlahDL,
                                    'ekspektasi' => $ekspektasi
                                ]
                            ]);
                        }
                    } else {
                        // Tidak ada absensi sama sekali
                        if ($ekspektasi > 0) {
                            $alphaList->push([
                                'nama' => $p->name, 
                                'jabatan' => $jabatan, 
                                'jam' => null, 
                                'status' => 'alpha',
                                'rekap' => [
                                    'h' => 0,
                                    'a' => $ekspektasi,
                                    'i' => 0,
                                    's' => 0,
                                    'dl' => 0,
                                    'ekspektasi' => $ekspektasi
                                ]
                            ]);
                        }
                    }
                }
            }

            $absensiPetugas = $hadirList->merge($belumAbsenList)->merge($alphaList)->values();
        }

        // Debug Log
        logger("=== DEBUG TAMPIL ===");
        logger("Periode: {$periodeTampilan} | Filter Hari: {$filterHari} | Jam: {$jamSekarang}");
        logger("Status: " . ($isLiburOtomatis ? 'LIBUR OTOMATIS' : 'AKTIF'));

        // ===== 5. GRAFIK & CHART =====
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

        // ===== 6. TABEL RANKING & AKTIVITAS =====
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
            'periodeTampilan'     => $periodeTampilan,
            'filter_hari'         => $filterHari,
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

        // ===== ABSENSI PETUGAS UNTUK DASHBOARD =====
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
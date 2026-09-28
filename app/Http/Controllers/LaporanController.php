<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Models\AbsensiPetugas;
use App\Models\BukuTamu;
use App\Models\IzinKeluar;
use App\Models\Keterlambatan;
use App\Models\Pelanggaran;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LaporanExport;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $jenis = $request->input('jenis', 'gabungan');
        $periode = $request->input('periode', 'harian');
        $tanggal = $request->input('tanggal', Carbon::now('Asia/Makassar')->toDateString());
        $semester = $request->input('semester', 'ganjil');
        $filterHari = $request->input('filter_hari', 'Semua Hari');

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 15;

        [$start, $end, $labelPeriode] = $this->hitungRentang($periode, $tanggal, $semester);
        $dataLengkap = $this->ambilData($jenis, $start, $end, $filterHari);
        $preview = $dataLengkap->forPage($page, $perPage)->values();

        return Inertia::render('Laporan/Index', [
            'jenis' => $jenis,
            'periode' => $periode,
            'tanggal' => $tanggal,
            'semester' => $semester,
            'filter_hari' => $filterHari,
            'labelPeriode' => $labelPeriode,
            'start' => $start->isoFormat('D MMMM Y'),
            'end' => $end->isoFormat('D MMMM Y'),
            'ringkasan' => [
                'total' => $dataLengkap->count(),
                'keterlambatan' => $dataLengkap->where('jenis_aktivitas', 'Keterlambatan')->count(),
                'izin_keluar' => $dataLengkap->where('jenis_aktivitas', 'Izin Keluar')->count(),
                'pelanggaran' => $dataLengkap->where('jenis_aktivitas', 'Pelanggaran')->count(),
                'tamu' => $dataLengkap->where('jenis_aktivitas', 'Tamu')->count(),
            ],
            'preview' => $preview,
            'pagination' => [
                'current_page' => $page,
                'last_page' => (int) ceil($dataLengkap->count() / $perPage),
                'per_page' => $perPage,
                'total' => $dataLengkap->count(),
            ],
            'params' => compact('jenis', 'periode', 'tanggal', 'semester', 'filter_hari'),
        ]);
    }

    public function excel(Request $request)
    {
        $jenis = $request->input('jenis', 'gabungan');
        $periode = $request->input('periode', 'harian');
        $tanggal = $request->input('tanggal', Carbon::now('Asia/Makassar')->toDateString());
        $semester = $request->input('semester', 'ganjil');
        $filterHari = $request->input('filter_hari', 'Semua Hari');

        [$start, $end, $labelPeriode] = $this->hitungRentang($periode, $tanggal, $semester);
        $data = $this->ambilData($jenis, $start, $end, $filterHari);

        $namaFile = 'Laporan_' . ucfirst($jenis) . '_' . ucfirst($periode) . '_' . $start->isoFormat('D-MMM-Y') . '.xlsx';

        return Excel::download(new LaporanExport($data, $labelPeriode, $jenis), $namaFile);
    }

    // ===== LAPORAN PIKET (PDF BERWARNA) =====
    public function pdf(Request $request)
    {
        try {
            if ($request->routeIs('tampil.*')) {
                $key = config('services.display.key');
                if ($key && $request->query('k') !== $key) {
                    abort(403, 'Akses ditolak.');
                }
            }

            $periode    = $request->input('periode', 'harian');
            $filterHari = $request->input('filter_hari', 'Semua Hari');
            $tanggal    = $request->input('tanggal', Carbon::now('Asia/Makassar')->toDateString());
            $semester   = $request->input('semester', 'ganjil');

            $tanggalRef = Carbon::parse($tanggal, 'Asia/Makassar');

            switch ($periode) {
                case 'mingguan':
                    $dari = $tanggalRef->copy()->startOfWeek();
                    $sampai = $tanggalRef->copy()->endOfWeek();
                    $labelPeriode = 'Mingguan — ' . $dari->isoFormat('D MMM') . ' s/d ' . $sampai->isoFormat('D MMM Y');
                    break;
                case 'bulanan':
                    $dari = $tanggalRef->copy()->startOfMonth();
                    $sampai = $tanggalRef->copy()->endOfMonth();
                    $labelPeriode = 'Bulanan — ' . $tanggalRef->isoFormat('MMMM Y');
                    break;
                case 'semester':
                    $bulan = $tanggalRef->month;
                    if ($semester === 'genap' || ($bulan >= 1 && $bulan <= 6)) {
                        $dari = Carbon::create($tanggalRef->year, 1, 1);
                        $sampai = Carbon::create($tanggalRef->year, 6, 30)->endOfDay();
                        $labelSemester = 'Genap';
                    } else {
                        $dari = Carbon::create($tanggalRef->year, 7, 1);
                        $sampai = Carbon::create($tanggalRef->year, 12, 31)->endOfDay();
                        $labelSemester = 'Ganjil';
                    }
                    $labelPeriode = 'Semester ' . $labelSemester . ' — ' . $tanggalRef->year;
                    break;
                default:
                    $dari = $tanggalRef->copy()->startOfDay();
                    $sampai = $tanggalRef->copy()->endOfDay();
                    $labelPeriode = 'Harian';
                    break;
            }

            $dariStr   = $dari->toDateString();
            $sampaiStr = $sampai->toDateString();
            $withSiswa = 'siswa:id,nisn,nis,nama,kelas,jurusan';

            $pengaturan = Pengaturan::first();

            // ===== FUNGSI HELPER: FILTER BERDASARKAN HARI =====
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

            $absensiPetugasQuery = AbsensiPetugas::whereBetween('tanggal', [$dariStr, $sampaiStr]);
            $applyDayFilter($absensiPetugasQuery, 'tanggal');
            $absensiPetugas = $absensiPetugasQuery->orderBy('tanggal')->orderBy('jam_masuk')->get();

            // ===== REKAP PETUGAS - FORMAT BERBEDA BERDASARKAN PERIODE =====
            $rekapPetugas = collect();
            $tidakAdaJadwal = false;
            $pesanLibur = '';
            $isLiburOtomatis = false;

            if ($periode === 'harian') {
                $namaHariLaporan = $filterHari !== 'Semua Hari' ? $filterHari : $tanggalRef->isoFormat('dddd');

                $semuaPetugasPdf = User::whereIn('role', ['petugas', 'koordinator'])->orderBy('name')->get();
                $namaDenganAbsensiPdf = AbsensiPetugas::whereDate('tanggal', $dariStr)
                    ->pluck('nama')
                    ->map(fn($n) => strtolower(trim($n)))
                    ->unique()
                    ->toArray();

                $petugasRelevanPdf = $semuaPetugasPdf->filter(function ($u) use ($namaHariLaporan, $namaDenganAbsensiPdf, $filterHari) {
                    $namaKey = strtolower(trim($u->name));
                    $cocokKelompok = ($filterHari === 'Semua Hari') ? ($u->hari_piket === $namaHariLaporan) : ($u->hari_piket === $namaHariLaporan);
                    return $cocokKelompok || in_array($namaKey, $namaDenganAbsensiPdf);
                });

                if ($petugasRelevanPdf->isEmpty()) {
                    $tidakAdaJadwal = true;
                    $pesanLibur = "Tidak ada petugas yang dijadwalkan atau absen untuk hari {$namaHariLaporan}.";
                }

                // ===== SINKRONISASI: LIBUR OTOMATIS (sama dengan card piket) =====
                $namaHariIni = Carbon::now('Asia/Makassar')->isoFormat('dddd');
                $totalAbsensiTanggal = AbsensiPetugas::whereDate('tanggal', $dariStr)->count();

                $isLiburOtomatis = ($namaHariLaporan === 'Minggu')
                    || ($namaHariLaporan === $namaHariIni
                        && $tanggalRef->isToday()
                        && $totalAbsensiTanggal === 0);

                if ($isLiburOtomatis) {
                    $rekapPetugas = collect();
                    $tidakAdaJadwal = true;
                    $pesanLibur = 'Tidak ada Petugas Piket yang dijadwalkan Hari ini';
                } else {
                    $rekapPetugas = $petugasRelevanPdf->map(function ($u) use ($dariStr, $tanggalRef) {
                        $r = AbsensiPetugas::where('nama', $u->name)
                            ->whereDate('tanggal', $dariStr)
                            ->orderBy('jam_masuk')
                            ->first();

                        return [
                            'nama'       => $u->name,
                            'jabatan'    => $u->role === 'koordinator' ? 'Koordinator Piket' : 'Guru Piket',
                            'jam'        => $r?->jam_masuk ?? '-',
                            'status'     => $r?->status ?? 'alpha',
                            'keterangan' => $r?->keterangan ?? '',
                            'tanggal'    => $r?->tanggal
                                ? Carbon::parse($r->tanggal, 'Asia/Makassar')->isoFormat('D MMM Y')
                                : $tanggalRef->isoFormat('D MMM Y'),
                            'format'     => 'harian',
                        ];
                    });
                }
            } else {
                $semuaPetugas = User::whereIn('role', ['petugas', 'koordinator'])->orderBy('name')->get();

                $absensiRentangQuery = AbsensiPetugas::whereBetween('tanggal', [$dariStr, $sampaiStr]);
                $applyDayFilter($absensiRentangQuery, 'tanggal');
                $absensiRentang = $absensiRentangQuery->get();

                $rekapPetugas = $semuaPetugas->filter(function ($u) use ($filterHari, $absensiRentang) {
                    $namaKey = strtolower(trim($u->name));
                    $punyaAbsensi = $absensiRentang->contains(function ($a) use ($namaKey) {
                        return strtolower(trim($a->nama)) === $namaKey;
                    });

                    $cocokKelompok = ($filterHari === 'Semua Hari') ? true : ($u->hari_piket === $filterHari);
                    return $cocokKelompok || $punyaAbsensi;
                })->map(function ($u) use ($absensiRentang) {
                    $absensiUser = $absensiRentang->filter(function ($a) use ($u) {
                        return strtolower(trim($a->nama)) === strtolower(trim($u->name));
                    });

                    $h = $absensiUser->filter(fn($a) => in_array($a->status, ['tepat_waktu', 'terlambat']))->count();
                    $a = $absensiUser->filter(fn($a) => $a->status === 'alpha')->count();
                    $i = $absensiUser->filter(fn($a) => $a->status === 'izin')->count();
                    $s = $absensiUser->filter(fn($a) => $a->status === 'sakit')->count();
                    $dl = $absensiUser->filter(fn($a) => $a->status === 'dl')->count();

                    return [
                        'nama'       => $u->name,
                        'jabatan'    => $u->role === 'koordinator' ? 'Koordinator Piket' : 'Guru Piket',
                        'h'          => $h,
                        'a'          => $a,
                        'i'          => $i,
                        's'          => $s,
                        'dl'         => $dl,
                        'total'      => $h + $a + $i + $s + $dl,
                        'format'     => 'rekap',
                    ];
                })->filter(fn($p) => $p['total'] > 0)->values();

                if ($rekapPetugas->isEmpty()) {
                    $tidakAdaJadwal = true;
                    $pesanLibur = "Tidak ada data rekap absensi petugas untuk periode dan filter hari ini.";
                }
            }

            $hadirHariIni = AbsensiPetugas::where('tanggal', $sampaiStr)
                ->whereIn('status', ['tepat_waktu', 'terlambat'])->count();

            $totalDijadwalkan = User::whereIn('role', ['petugas', 'koordinator'])->count();
            $alphaHariIni = max(0, $totalDijadwalkan - $hadirHariIni);

            // ===== DATA SISWA & TAMU (Diterapkan Filter Hari) =====
            $keterlambatanQuery = Keterlambatan::with($withSiswa)->whereBetween('tanggal', [$dariStr, $sampaiStr]);
            $applyDayFilter($keterlambatanQuery, 'tanggal');
            $keterlambatan = $keterlambatanQuery->orderBy('tanggal')->get();

            $izinQuery = IzinKeluar::with($withSiswa)->whereBetween('tanggal', [$dariStr, $sampaiStr]);
            $applyDayFilter($izinQuery, 'tanggal');
            $izinKeluar = $izinQuery->orderBy('tanggal')->get();

            $pelanggaranQuery = Pelanggaran::with($withSiswa)->whereBetween('tanggal', [$dariStr, $sampaiStr]);
            $applyDayFilter($pelanggaranQuery, 'tanggal');
            $pelanggaran = $pelanggaranQuery->orderBy('tanggal')->get();

            $tamuQuery = BukuTamu::whereBetween('tanggal_kunjungan', [$dariStr, $sampaiStr]);
            $applyDayFilter($tamuQuery, 'tanggal_kunjungan');
            $tamu = $tamuQuery->orderBy('tanggal_kunjungan')->get();

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
                ['label' => 'Total Siswa Aktif', 'nilai' => Siswa::where('aktif', true)->count() . ' siswa'],
                ['label' => 'Petugas Piket Hadir', 'nilai' => $hadirHariIni . ' orang'],
                ['label' => 'Petugas Alpha', 'nilai' => $alphaHariIni . ' orang'],
                ['label' => 'Keterlambatan Siswa', 'nilai' => $keterlambatan->count() . ' kejadian'],
                ['label' => 'Izin Keluar', 'nilai' => $izinKeluar->count() . ' kejadian'],
                ['label' => 'Pelanggaran', 'nilai' => $pelanggaran->count() . ' kejadian (' . $pelanggaran->sum('poin') . ' poin)'],
                ['label' => 'Kunjungan Tamu', 'nilai' => $tamu->count() . ' kunjungan'],
            ];

            // ===== NETRALKAN RINGKASAN PETUGAS SAAT LIBUR OTOMATIS =====
            if ($periode === 'harian' && $isLiburOtomatis) {
                $ringkasan[1]['nilai'] = '0 orang (Libur)';
                $ringkasan[2]['nilai'] = '0 orang (Libur)';
            }

            $logo = null;
            if ($pengaturan?->logo && Storage::disk('public')->exists($pengaturan->logo)) {
                $mime = Storage::disk('public')->mimeType($pengaturan->logo) ?: 'image/png';
                $logo = 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($pengaturan->logo));
            }

            $logoInstansi = null;
            if ($pengaturan?->logo_instansi && Storage::disk('public')->exists($pengaturan->logo_instansi)) {
                $mime = Storage::disk('public')->mimeType($pengaturan->logo_instansi) ?: 'image/png';
                $logoInstansi = 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($pengaturan->logo_instansi));
            }

            $koordinator = User::where('role', 'koordinator')->orderBy('name')->first();
            $tempatTanggal = ($pengaturan->kota ?? 'Kolaka') . ', ' . Carbon::now('Asia/Makassar')->isoFormat('D MMMM Y');

            $totalData = $absensiPetugas->count()
                + $keterlambatan->count()
                + $izinKeluar->count()
                + $pelanggaran->count()
                + $tamu->count();

            $data = [
                'pengaturan'       => $pengaturan,
                'logo'             => $logo,
                'logoInstansi'     => $logoInstansi,
                'labelPeriode'     => $labelPeriode,
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
                'dicetakOleh'      => auth()->user()?->name ?? 'Sistem Otomatis',
                'waktuCetak'       => Carbon::now('Asia/Makassar')->format('d-m-Y H:i'),
                'koordinator'      => $koordinator,
                'tempatTanggal'    => $tempatTanggal,
                'periode'          => $periode,
                'filter_hari'      => $filterHari,
                'tidakAdaJadwal'   => $tidakAdaJadwal,
                'pesanLibur'       => $pesanLibur,
            ];

            $pdf = Pdf::loadView('laporan.pdf', $data)->setPaper('a4', 'portrait');
            return $pdf->download('Laporan-Piket-' . $periode . '-' . $dariStr . '.pdf');
        } catch (\Throwable $e) {
            Log::error('PDF Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'error' => 'Gagal generate PDF: ' . $e->getMessage(),
                'file'  => basename($e->getFile()),
                'line'  => $e->getLine(),
            ], 500);
        }
    }

    // ===== DAFTAR HADIR PIKET (CHECKLIST) =====
    public function daftarHadir(Request $request)
    {
        if ($request->routeIs('tampil.*')) {
            $key = config('services.display.key');
            if ($key && $request->query('k') !== $key) abort(403, 'Akses ditolak.');
        }

        $periode     = $request->input('periode', 'harian');
        $filterHari  = $request->input('filter_hari', 'Semua Hari');
        $tanggal     = $request->input('tanggal', Carbon::now('Asia/Makassar')->toDateString());
        $semester    = $request->input('semester', 'ganjil');
        $mode        = $request->input('mode', 'hadir');
        $dariInput   = $request->input('dari');
        $sampaiInput = $request->input('sampai');

        $tanggalRef = Carbon::parse($tanggal, 'Asia/Makassar');

        if ($periode === 'rentang' && $dariInput && $sampaiInput) {
            $dari   = Carbon::parse($dariInput, 'Asia/Makassar')->startOfDay();
            $sampai = Carbon::parse($sampaiInput, 'Asia/Makassar')->endOfDay();
        } else {
            switch ($periode) {
                case 'mingguan':
                    $dari = $tanggalRef->copy()->startOfWeek(Carbon::MONDAY);
                    $sampai = $tanggalRef->copy()->endOfWeek(Carbon::SUNDAY);
                    break;
                case 'bulanan':
                    $dari = $tanggalRef->copy()->startOfMonth();
                    $sampai = $tanggalRef->copy()->endOfMonth();
                    break;
                case 'semester':
                    $bulan = $tanggalRef->month;
                    if ($semester === 'genap' || ($bulan >= 1 && $bulan <= 6)) {
                        $dari = Carbon::create($tanggalRef->year, 1, 1);
                        $sampai = Carbon::create($tanggalRef->year, 6, 30)->endOfDay();
                    } else {
                        $dari = Carbon::create($tanggalRef->year, 7, 1);
                        $sampai = Carbon::create($tanggalRef->year, 12, 31)->endOfDay();
                    }
                    break;
                default:
                    $dari = $tanggalRef->copy()->startOfDay();
                    $sampai = $tanggalRef->copy()->endOfDay();
            }
        }

        $dariStr   = $dari->toDateString();
        $sampaiStr = min($sampai->toDateString(), Carbon::now('Asia/Makassar')->toDateString());

        $namaHariTanggal = $filterHari !== 'Semua Hari' ? $filterHari : $tanggalRef->isoFormat('dddd');

        $semuaPetugas = User::whereIn('role', ['petugas', 'koordinator'])->orderBy('name')->get();
        $namaDenganAbsensi = AbsensiPetugas::whereBetween('tanggal', [$dariStr, $sampaiStr])
            ->pluck('nama')
            ->map(fn($n) => strtolower(trim($n)))
            ->unique()
            ->toArray();

        $petugasRelevan = $semuaPetugas->filter(function ($u) use ($namaHariTanggal, $namaDenganAbsensi, $filterHari) {
            $namaKey = strtolower(trim($u->name));
            $punyaAbsensiDiRentang = in_array($namaKey, $namaDenganAbsensi);
            $cocokKelompok = ($filterHari === 'Semua Hari') ? ($u->hari_piket === $namaHariTanggal) : ($u->hari_piket === $namaHariTanggal);

            return $cocokKelompok || $punyaAbsensiDiRentang;
        })->values();

        $rows = $petugasRelevan->map(function ($u) use ($dari, $sampaiStr, $dariStr, $periode, $filterHari) {
            $absensiUserQuery = AbsensiPetugas::where('nama', $u->name)->whereBetween('tanggal', [$dariStr, $sampaiStr]);
            if ($filterHari !== 'Semua Hari') {
                $dayMap = ['Minggu' => 1, 'Senin' => 2, 'Selasa' => 3, 'Rabu' => 4, 'Kamis' => 5, 'Jumat' => 6, 'Sabtu' => 7];
                $dayNum = $dayMap[$filterHari] ?? null;
                if ($dayNum) {
                    $absensiUserQuery->whereRaw("DAYOFWEEK(tanggal) = ?", [$dayNum]);
                }
            }
            $absensiUser = $absensiUserQuery->get();

            if ($periode === 'harian') {
                $absen = $absensiUser->first();
                if ($absen) {
                    $h  = in_array($absen->status, ['tepat_waktu', 'terlambat']) ? 1 : 0;
                    $a  = $absen->status === 'alpha' ? 1 : 0;
                    $i  = $absen->status === 'izin' ? 1 : 0;
                    $s  = $absen->status === 'sakit' ? 1 : 0;
                    $dl = $absen->status === 'dl' ? 1 : 0;
                } else {
                    $h = 0;
                    $a = 1;
                    $i = 0;
                    $s = 0;
                    $dl = 0;
                }
            } else {
                $ekspektasi = 0;
                $cursor = $dari->copy();
                $sekarang = Carbon::now('Asia/Makassar')->toDateString();

                while ($cursor->toDateString() <= $sampaiStr) {
                    $hariIniCursor = $cursor->isoFormat('dddd');
                    $cocokHari = ($filterHari === 'Semua Hari') ? ($u->hari_piket === $hariIniCursor) : ($filterHari === $hariIniCursor);

                    if ($cocokHari) {
                        if ($cursor->toDateString() <= $sekarang) {
                            $ekspektasi++;
                        }
                    }
                    $cursor->addDay();
                }

                $statuses = $absensiUser->groupBy(fn($r) => \Carbon\Carbon::parse($r->tanggal)->toDateString())
                    ->map(fn($grup) => $grup->first()->status)->values();

                $h  = $statuses->filter(fn($st) => in_array($st, ['tepat_waktu', 'terlambat']))->count();
                $i  = $statuses->filter(fn($st) => $st === 'izin')->count();
                $s  = $statuses->filter(fn($st) => $st === 'sakit')->count();
                $dl = $statuses->filter(fn($st) => $st === 'dl')->count();
                $a  = max(0, $ekspektasi - ($h + $i + $s + $dl));
            }

            return [
                'nama'   => $u->name,
                'jk'     => $u->jenis_kelamin ?? '',
                'nip'    => $u->nip ?? '',
                'gol'    => $u->golongan ?? '',
                'status' => $u->status_kepegawaian ?? '',
                'h'      => $h, 'a' => $a, 'i' => $i, 's' => $s, 'dl' => $dl,
                'ket'    => '',
            ];
        });

        // ===== SINKRONISASI: LIBUR OTOMATIS (sama dengan card piket) =====
        $namaHariIni = Carbon::now('Asia/Makassar')->isoFormat('dddd');
        $totalAbsensiTanggal = AbsensiPetugas::whereDate('tanggal', $dariStr)->count();

        $isLibur = ($periode === 'harian') && (
            ($namaHariTanggal === 'Minggu') ||
            ($namaHariTanggal === $namaHariIni && $tanggalRef->isToday() && $totalAbsensiTanggal === 0)
        );

        $pesanLibur = null;
        if ($isLibur) {
            $rows = collect(); // kosongkan agar tidak tampil 13 alpha
            $pesanLibur = 'Tidak ada Petugas Piket yang dijadwalkan Hari ini';
        }

        $pengaturan = Pengaturan::first();
        $logo = null;
        if ($pengaturan?->logo && Storage::disk('public')->exists($pengaturan->logo)) {
            $mime = Storage::disk('public')->mimeType($pengaturan->logo) ?: 'image/png';
            $logo = 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($pengaturan->logo));
        }
        $logoInstansi = null;
        if ($pengaturan?->logo_instansi && Storage::disk('public')->exists($pengaturan->logo_instansi)) {
            $mime = Storage::disk('public')->mimeType($pengaturan->logo_instansi) ?: 'image/png';
            $logoInstansi = 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($pengaturan->logo_instansi));
        }

        $hariTanggal = $periode === 'harian'
            ? $tanggalRef->isoFormat('dddd, D MMMM Y')
            : $dari->isoFormat('D MMMM Y') . ' s/d ' . Carbon::parse($sampaiStr, 'Asia/Makassar')->isoFormat('D MMMM Y');

        $koordinator = User::where('role', 'koordinator')->orderBy('name')->first();
        $tempatTanggal = ($pengaturan->kota ?? 'Kolaka') . ', ' . Carbon::now('Asia/Makassar')->isoFormat('D MMMM Y');

        $pdf = Pdf::loadView('laporan.daftar-hadir', [
            'pengaturan'    => $pengaturan,
            'logo'          => $logo,
            'logoInstansi'  => $logoInstansi,
            'rows'          => $rows,
            'hariTanggal'   => $hariTanggal,
            'koordinator'   => $koordinator,
            'tempatTanggal' => $tempatTanggal,
            'mode'          => $mode,
            'periode'       => $periode,
            'filter_hari'   => $filterHari,
            'pesanLibur'    => $pesanLibur,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Daftar-Hadir-Piket-' . $periode . '-' . $dariStr . '.pdf');
    }

    private function hitungRentang(string $periode, string $tanggal, string $semester): array
    {
        $date = Carbon::parse($tanggal, 'Asia/Makassar');

        switch ($periode) {
            case 'mingguan':
                $start = $date->copy()->startOfWeek(Carbon::MONDAY);
                $end = $date->copy()->endOfWeek(Carbon::SUNDAY);
                $label = 'Minggu ' . $start->isoFormat('D MMM') . ' - ' . $end->isoFormat('D MMM Y');
                break;
            case 'bulanan':
                $start = $date->copy()->startOfMonth();
                $end = $date->copy()->endOfMonth();
                $label = 'Bulan ' . $start->isoFormat('MMMM Y');
                break;
            case 'semester':
                $tahun = $date->year;
                if ($semester === 'genap') {
                    $start = Carbon::create($tahun, 1, 1);
                    $end = Carbon::create($tahun, 6, 30);
                    $label = 'Semester Genap (Januari - Juni ' . $tahun . ')';
                } else {
                    $start = Carbon::create($tahun, 7, 1);
                    $end = Carbon::create($tahun, 12, 31);
                    $label = 'Semester Ganjil (Juli - Desember ' . $tahun . ')';
                }
                break;
            case 'harian':
            default:
                $start = $date->copy()->startOfDay();
                $end = $date->copy()->endOfDay();
                $label = 'Tanggal ' . $start->isoFormat('D MMMM Y');
                break;
        }

        return [$start, $end, $label];
    }

    private function ambilData(string $jenis, Carbon $start, Carbon $end, string $filterHari = 'Semua Hari')
    {
        $data = collect();

        $applyDayFilter = function ($query, $columnName = 'tanggal') use ($filterHari) {
            if ($filterHari !== 'Semua Hari') {
                $dayMap = ['Minggu' => 1, 'Senin' => 2, 'Selasa' => 3, 'Rabu' => 4, 'Kamis' => 5, 'Jumat' => 6, 'Sabtu' => 7];
                $dayNum = $dayMap[$filterHari] ?? null;
                if ($dayNum) {
                    $query->whereRaw("DAYOFWEEK({$columnName}) = ?", [$dayNum]);
                }
            }
            return $query;
        };

        if (in_array($jenis, ['gabungan', 'keterlambatan'])) {
            $query = Keterlambatan::with('siswa:id,nisn,nama,kelas')->whereBetween('tanggal', [$start, $end]);
            $applyDayFilter($query, 'tanggal');
            $query->orderByDesc('tanggal')->get()->each(function ($k) use ($data) {
                $data->push([
                    'jenis_aktivitas' => 'Keterlambatan',
                    'tanggal' => $k->tanggal?->isoFormat('D MMM Y') ?? '-',
                    'jam' => $k->jam_datang ?? '-',
                    'siswa' => $k->siswa?->nama ?? '-',
                    'kelas' => $k->siswa?->kelas ?? '-',
                    'nisn' => $k->siswa?->nisn ?? '-',
                    'detail' => ($k->menit_terlambat ?? 0) . ' menit',
                    'keterangan' => $k->keterangan ?? '-',
                    'status' => $k->status ?? '-',
                ]);
            });
        }

        if (in_array($jenis, ['gabungan', 'izin_keluar'])) {
            $query = IzinKeluar::with('siswa:id,nisn,nama,kelas')->whereBetween('tanggal', [$start, $end]);
            $applyDayFilter($query, 'tanggal');
            $query->orderByDesc('tanggal')->get()->each(function ($i) use ($data) {
                $data->push([
                    'jenis_aktivitas' => 'Izin Keluar',
                    'tanggal' => $i->tanggal?->isoFormat('D MMM Y') ?? '-',
                    'jam' => $i->jam_keluar ?? '-',
                    'siswa' => $i->siswa?->nama ?? '-',
                    'kelas' => $i->siswa?->kelas ?? '-',
                    'nisn' => $i->siswa?->nisn ?? '-',
                    'detail' => ($i->jenis ?? '-') . ($i->jam_kembali ? ' (kembali ' . $i->jam_kembali . ')' : ''),
                    'keterangan' => $i->keterangan ?? '-',
                    'status' => $i->status ?? '-',
                ]);
            });
        }

        if (in_array($jenis, ['gabungan', 'pelanggaran'])) {
            $query = Pelanggaran::with('siswa:id,nisn,nama,kelas')->whereBetween('tanggal', [$start, $end]);
            $applyDayFilter($query, 'tanggal');
            $query->orderByDesc('tanggal')->get()->each(function ($p) use ($data) {
                $data->push([
                    'jenis_aktivitas' => 'Pelanggaran',
                    'tanggal' => $p->tanggal?->isoFormat('D MMM Y') ?? '-',
                    'jam' => '-',
                    'siswa' => $p->siswa?->nama ?? '-',
                    'kelas' => $p->siswa?->kelas ?? '-',
                    'nisn' => $p->siswa?->nisn ?? '-',
                    'detail' => ($p->jenis_pelanggaran ?? '-') . ' (' . ($p->poin ?? 0) . ' poin)',
                    'keterangan' => $p->keterangan ?? '-',
                    'status' => $p->status ?? '-',
                ]);
            });
        }

        if (in_array($jenis, ['gabungan', 'tamu'])) {
            $query = BukuTamu::whereBetween('tanggal_kunjungan', [$start, $end]);
            $applyDayFilter($query, 'tanggal_kunjungan');
            $query->orderByDesc('tanggal_kunjungan')->get()->each(function ($t) use ($data) {
                $data->push([
                    'jenis_aktivitas' => 'Tamu',
                    'tanggal' => $t->tanggal_kunjungan?->isoFormat('D MMM Y') ?? '-',
                    'jam' => $t->jam_masuk ?? '-',
                    'siswa' => $t->nama ?? '-',
                    'kelas' => $t->instansi ?? '-',
                    'nisn' => $t->telepon ?? '-',
                    'detail' => 'Bertemu: ' . ($t->bertemu_dengan ?? '-') . ' | ' . ($t->keperluan ?? '-'),
                    'keterangan' => $t->catatan ?? '-',
                    'status' => $t->jam_keluar ? 'Sudah keluar' : 'Masih di sekolah',
                ]);
            });
        }

        return $data->sortByDesc('tanggal')->values();
    }
}
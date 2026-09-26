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
        // ✅ PERBAIKAN: Default tanggal menggunakan WITA
        $tanggal = $request->input('tanggal', Carbon::now('Asia/Makassar')->toDateString());
        $semester = $request->input('semester', 'ganjil');

        [$start, $end, $labelPeriode] = $this->hitungRentang($periode, $tanggal, $semester);
        $data = $this->ambilData($jenis, $start, $end);

        return Inertia::render('Laporan/Index', [
            'jenis' => $jenis,
            'periode' => $periode,
            'tanggal' => $tanggal,
            'semester' => $semester,
            'labelPeriode' => $labelPeriode,
            'start' => $start->isoFormat('D MMMM Y'),
            'end' => $end->isoFormat('D MMMM Y'),
            'ringkasan' => [
                'total' => $data->count(),
                'keterlambatan' => $data->where('jenis_aktivitas', 'Keterlambatan')->count(),
                'izin_keluar' => $data->where('jenis_aktivitas', 'Izin Keluar')->count(),
                'pelanggaran' => $data->where('jenis_aktivitas', 'Pelanggaran')->count(),
                'tamu' => $data->where('jenis_aktivitas', 'Tamu')->count(),
            ],
            'preview' => $data->values(),
            'params' => compact('jenis', 'periode', 'tanggal', 'semester'),
        ]);
    }

    public function excel(Request $request)
    {
        $jenis = $request->input('jenis', 'gabungan');
        $periode = $request->input('periode', 'harian');
        // ✅ PERBAIKAN: Default tanggal menggunakan WITA
        $tanggal = $request->input('tanggal', Carbon::now('Asia/Makassar')->toDateString());
        $semester = $request->input('semester', 'ganjil');

        [$start, $end, $labelPeriode] = $this->hitungRentang($periode, $tanggal, $semester);
        $data = $this->ambilData($jenis, $start, $end);

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

            $periode  = $request->input('periode', 'harian');
            // ✅ PERBAIKAN 1: Paksa default tanggal menggunakan timezone WITA
            $tanggal  = $request->input('tanggal', Carbon::now('Asia/Makassar')->toDateString());
            $semester = $request->input('semester', 'ganjil');

            // ✅ PERBAIKAN 2: Parse tanggal dengan timezone WITA
            $tanggalRef = Carbon::parse($tanggal, 'Asia/Makassar');

            switch ($periode) {
                case 'mingguan':
                    $dari = $tanggalRef->copy()->startOfWeek();
                    $sampai = $tanggalRef->copy()->endOfWeek();
                    $labelPeriode = 'Mingguan — '.$dari->isoFormat('D MMM').' s/d '.$sampai->isoFormat('D MMM Y');
                    break;
                case 'bulanan':
                    $dari = $tanggalRef->copy()->startOfMonth();
                    $sampai = $tanggalRef->copy()->endOfMonth();
                    $labelPeriode = 'Bulanan — '.$tanggalRef->isoFormat('MMMM Y');
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
                    $labelPeriode = 'Semester '.$labelSemester.' — '.$tanggalRef->year;
                    break;
                default:
                    $dari = $tanggalRef->copy()->startOfDay();
                    $sampai = $tanggalRef->copy()->endOfDay();
                    $labelPeriode = 'Harian — '.$tanggalRef->isoFormat('dddd, D MMMM Y');
            }

            $dariStr   = $dari->toDateString();
            $sampaiStr = $sampai->toDateString();
            $withSiswa = 'siswa:id,nisn,nis,nama,kelas,jurusan';

            // ✅ PERBAIKAN 3: Ambil data pengaturan DI AWAL agar tidak error undefined variable
            $pengaturan = Pengaturan::first();

            // ✅ PERBAIKAN 4: Query absensi petugas (sebelumnya hilang di kode Anda)
            $absensiPetugas = AbsensiPetugas::whereBetween('tanggal', [$dariStr, $sampaiStr])
                ->orderBy('tanggal')->orderBy('jam_masuk')->get();

            // ===== REKAP SEMUA PETUGAS =====
            $rekapPetugas = User::whereIn('role', ['petugas', 'koordinator'])
                ->orderBy('name')
                ->get()
                ->map(function ($u) use ($dariStr, $sampaiStr, $periode, $tanggalRef, $dari, $sampai) {
                    $r = AbsensiPetugas::where('nama', $u->name)
                        ->whereBetween('tanggal', [$dariStr, $sampaiStr])
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
                                            : ($periode === 'harian'
                                                ? $tanggalRef->isoFormat('D MMM Y')
                                                : $dari->isoFormat('D MMM').' – '.$sampai->isoFormat('D MMM Y')),
                    ];
                });

            $hadirHariIni = AbsensiPetugas::where('tanggal', $sampaiStr)
                ->whereIn('status', ['tepat_waktu', 'terlambat'])->count();
            $alphaHariIni = max(0, User::whereIn('role', ['petugas', 'koordinator'])->count() - $hadirHariIni);

            $keterlambatan = Keterlambatan::with($withSiswa)->whereBetween('tanggal', [$dariStr, $sampaiStr])->orderBy('tanggal')->get();
            $izinKeluar    = IzinKeluar::with($withSiswa)->whereBetween('tanggal', [$dariStr, $sampaiStr])->orderBy('tanggal')->get();
            $pelanggaran   = Pelanggaran::with($withSiswa)->whereBetween('tanggal', [$dariStr, $sampaiStr])->orderBy('tanggal')->get();
            $tamu          = BukuTamu::whereBetween('tanggal_kunjungan', [$dariStr, $sampaiStr])->orderBy('tanggal_kunjungan')->get();

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

            // ===== KOP + LOGO =====
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

            // ===== DATA TANDA TANGAN =====
            $koordinator = User::where('role', 'koordinator')->orderBy('name')->first();
            // ✅ PERBAIKAN 5: Tempat tanggal pakai WITA
            $tempatTanggal = ($pengaturan->kota ?? 'Kolaka').', '.Carbon::now('Asia/Makassar')->isoFormat('D MMMM Y');

            $totalData = $absensiPetugas->count()
                       + $keterlambatan->count()
                       + $izinKeluar->count()
                       + $pelanggaran->count()
                       + $tamu->count();

            $data = [
                'pengaturan'        => $pengaturan,
                'logo'              => $logo,
                'logoInstansi'      => $logoInstansi,
                'labelPeriode'      => $labelPeriode,
                'rekapPetugas'      => $rekapPetugas,
                'ringkasan'         => $ringkasan,
                'keterlambatan'     => $keterlambatan,
                'izinKeluar'        => $izinKeluar,
                'pelanggaran'       => $pelanggaran,
                'tamu'              => $tamu,
                'perKelas'          => $perKelas,
                'perJurusan'        => $perJurusan,
                'jenisPelanggaran'  => $jenisPelanggaran,
                'topPoin'           => $topPoin,
                'topTerlambat'      => $topTerlambat,
                'totalData'         => $totalData,
                'dicetakOleh'       => auth()->user()?->name ?? 'Sistem Otomatis',
                'waktuCetak'        => Carbon::now('Asia/Makassar')->format('d-m-Y H:i'),
                'koordinator'       => $koordinator,
                'tempatTanggal'     => $tempatTanggal,
            ];

            $pdf = Pdf::loadView('laporan.pdf', $data)->setPaper('a4', 'portrait');
            return $pdf->download('Laporan-Piket-'.$periode.'-'.$dariStr.'.pdf');

        } catch (\Throwable $e) {
            Log::error('PDF Error: '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
            return response()->json([
                'error' => 'Gagal generate PDF: '.$e->getMessage(),
                'file'  => basename($e->getFile()),
                'line'  => $e->getLine(),
            ], 500);
        }
    }

    // ===== DAFTAR HADIR PIKET =====
    public function daftarHadir(Request $request)
    {
        if ($request->routeIs('tampil.*')) {
            $key = config('services.display.key');
            if ($key && $request->query('k') !== $key) abort(403, 'Akses ditolak.');
        }

        $periode     = $request->input('periode', 'harian');
        // ✅ PERBAIKAN 6: Default tanggal pakai WITA
        $tanggal     = $request->input('tanggal', Carbon::now('Asia/Makassar')->toDateString());
        $semester    = $request->input('semester', 'ganjil');
        $mode        = $request->input('mode', 'hadir');
        $dariInput   = $request->input('dari');
        $sampaiInput = $request->input('sampai');

        // ✅ PERBAIKAN 7: Parse tanggal dengan WITA
        $tanggalRef = Carbon::parse($tanggal, 'Asia/Makassar');

        if ($periode === 'rentang' && $dariInput && $sampaiInput) {
            $dari   = Carbon::parse($dariInput, 'Asia/Makassar')->startOfDay();
            $sampai = Carbon::parse($sampaiInput, 'Asia/Makassar')->endOfDay();
        } else {
            switch ($periode) {
                case 'mingguan':
                    $dari = $tanggalRef->copy()->startOfWeek();
                    $sampai = $tanggalRef->copy()->endOfWeek();
                    break;
                case 'bulanan':
                    $dari = $tanggalRef->copy()->startOfMonth();
                    $sampai = $tanggalRef->copy()->endOfMonth();
                    break;
                case 'semester':
                    $bulan = $tanggalRef->month;
                    if ($semester === 'genap' || ($bulan >= 1 && $bulan <= 6)) {
                        $dari = Carbon::create($tanggalRef->year, 1, 1);
                        $sampai = Carbon::create($tanggalRef->year, 6, 30);
                    } else {
                        $dari = Carbon::create($tanggalRef->year, 7, 1);
                        $sampai = Carbon::create($tanggalRef->year, 12, 31);
                    }
                    break;
                default:
                    $dari = $tanggalRef->copy()->startOfDay();
                    $sampai = $tanggalRef->copy()->endOfDay();
            }
        }

        $dariStr   = $dari->toDateString();
        // ✅ PERBAIKAN 8: now() diganti dengan Carbon::now('Asia/Makassar')
        $sampaiStr = min($sampai->toDateString(), Carbon::now('Asia/Makassar')->toDateString());

        $mapHari = [
            'Minggu' => 0, 'Senin' => 1, 'Selasa' => 2, 'Rabu' => 3,
            'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6,
        ];

        $rows = User::whereIn('role', ['petugas', 'koordinator'])->orderBy('name')->get()
            ->map(function ($u) use ($dariStr, $sampaiStr, $dari, $mapHari) {
                $norm = fn ($v) => $v instanceof \DateTimeInterface
                    ? $v->format('Y-m-d')
                    : substr((string) $v, 0, 10);

                $semua = AbsensiPetugas::where('nama', $u->name)->get();

                $hariPiket = $u->hari_piket !== null
                    ? ($mapHari[$u->hari_piket] ?? null)
                    : null;

                if ($hariPiket === null) {
                    $hariPiket = $semua
                        ->groupBy(fn ($r) => Carbon::parse($norm($r->tanggal), 'Asia/Makassar')->dayOfWeek)
                        ->sortByDesc(fn ($grup) => $grup->count())
                        ->keys()
                        ->first();
                }

                $ekspektasi = 0;
                if ($hariPiket !== null) {
                    $cursor = $dari->copy();
                    while ($cursor->toDateString() <= $sampaiStr) {
                        if ($cursor->dayOfWeek === $hariPiket) {
                            // ✅ PERBAIKAN 9: Cek waktu alpha pakai WITA
                            if ($cursor->toDateString() === Carbon::now('Asia/Makassar')->toDateString()
                                && Carbon::now('Asia/Makassar')->format('H:i') < '08:30') {
                                // belum lewat batas
                            } else {
                                $ekspektasi++;
                            }
                        }
                        $cursor->addDay();
                    }
                }

                $records = $semua->filter(
                    fn ($r) => $norm($r->tanggal) >= $dariStr && $norm($r->tanggal) <= $sampaiStr
                );

                $statuses = $records
                    ->groupBy(fn ($r) => $norm($r->tanggal))
                    ->map(fn ($grup) => $grup->first()->status)
                    ->values();

                $h  = $statuses->filter(fn ($st) => in_array($st, ['tepat_waktu', 'terlambat']))->count();
                $iz = $statuses->filter(fn ($st) => $st === 'izin')->count();
                $sk = $statuses->filter(fn ($st) => $st === 'sakit')->count();
                $dl = $statuses->filter(fn ($st) => $st === 'dl')->count();
                $a  = max(0, $ekspektasi - $statuses->count());

                return [
                    'nama'   => $u->name,
                    'jk'     => $u->jenis_kelamin ?? '',
                    'nip'    => $u->nip ?? '',
                    'gol'    => $u->golongan ?? '',
                    'status' => $u->status_kepegawaian ?? '',
                    'h'      => $h,
                    'a'      => $a,
                    'i'      => $iz,
                    's'      => $sk,
                    'dl'     => $dl,
                    'ket'    => '',
                ];
            });

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

        $hariTanggal = $periode === 'harian'
            ? $tanggalRef->isoFormat('dddd, D MMMM Y')
            : $dari->isoFormat('D MMMM Y').' s/d '.Carbon::parse($sampaiStr, 'Asia/Makassar')->isoFormat('D MMMM Y');

        $koordinator = User::where('role', 'koordinator')->orderBy('name')->first();
        // ✅ PERBAIKAN 10: Tempat tanggal pakai WITA
        $tempatTanggal = ($pengaturan->kota ?? 'Kolaka').', '.Carbon::now('Asia/Makassar')->isoFormat('D MMMM Y');

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
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Daftar-Hadir-Piket-'.$periode.'-'.$dariStr.'.pdf');
    }

    private function hitungRentang(string $periode, string $tanggal, string $semester): array
    {
        // ✅ PERBAIKAN 11: Parse tanggal dengan WITA di helper ini
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

    private function ambilData(string $jenis, Carbon $start, Carbon $end)
    {
        $data = collect();

        if (in_array($jenis, ['gabungan', 'keterlambatan'])) {
            Keterlambatan::with('siswa:id,nisn,nama,kelas')
                ->whereBetween('tanggal', [$start, $end])
                ->orderByDesc('tanggal')
                ->get()
                ->each(function ($k) use ($data) {
                    $data->push([
                        'jenis_aktivitas' => 'Keterlambatan',
                        'tanggal' => $k->tanggal->isoFormat('D MMM Y'),
                        'jam' => $k->jam_datang,
                        'siswa' => $k->siswa?->nama ?? '-',
                        'kelas' => $k->siswa?->kelas ?? '-',
                        'nisn' => $k->siswa?->nisn ?? '-',
                        'detail' => $k->menit_terlambat . ' menit',
                        'keterangan' => $k->keterangan ?? '-',
                        'status' => $k->status,
                    ]);
                });
        }

        if (in_array($jenis, ['gabungan', 'izin_keluar'])) {
            IzinKeluar::with('siswa:id,nisn,nama,kelas')
                ->whereBetween('tanggal', [$start, $end])
                ->orderByDesc('tanggal')
                ->get()
                ->each(function ($i) use ($data) {
                    $data->push([
                        'jenis_aktivitas' => 'Izin Keluar',
                        'tanggal' => $i->tanggal->isoFormat('D MMM Y'),
                        'jam' => $i->jam_keluar,
                        'siswa' => $i->siswa?->nama ?? '-',
                        'kelas' => $i->siswa?->kelas ?? '-',
                        'nisn' => $i->siswa?->nisn ?? '-',
                        'detail' => $i->jenis . ($i->jam_kembali ? ' (kembali ' . $i->jam_kembali . ')' : ''),
                        'keterangan' => $i->keterangan ?? '-',
                        'status' => $i->status,
                    ]);
                });
        }

        if (in_array($jenis, ['gabungan', 'pelanggaran'])) {
            Pelanggaran::with('siswa:id,nisn,nama,kelas')
                ->whereBetween('tanggal', [$start, $end])
                ->orderByDesc('tanggal')
                ->get()
                ->each(function ($p) use ($data) {
                    $data->push([
                        'jenis_aktivitas' => 'Pelanggaran',
                        'tanggal' => $p->tanggal->isoFormat('D MMM Y'),
                        'jam' => '-',
                        'siswa' => $p->siswa?->nama ?? '-',
                        'kelas' => $p->siswa?->kelas ?? '-',
                        'nisn' => $p->siswa?->nisn ?? '-',
                        'detail' => $p->jenis_pelanggaran . ' (' . $p->poin . ' poin)',
                        'keterangan' => $p->keterangan ?? '-',
                        'status' => $p->status,
                    ]);
                });
        }

        if (in_array($jenis, ['gabungan', 'tamu'])) {
            BukuTamu::whereBetween('tanggal_kunjungan', [$start, $end])
                ->orderByDesc('tanggal_kunjungan')
                ->get()
                ->each(function ($t) use ($data) {
                    $data->push([
                        'jenis_aktivitas' => 'Tamu',
                        'tanggal' => $t->tanggal_kunjungan->isoFormat('D MMM Y'),
                        'jam' => $t->jam_masuk,
                        'siswa' => $t->nama,
                        'kelas' => $t->instansi ?? '-',
                        'nisn' => $t->telepon ?? '-',
                        'detail' => 'Bertemu: ' . ($t->bertemu_dengan ?? '-') . ' | ' . $t->keperluan,
                        'keterangan' => $t->catatan ?? '-',
                        'status' => $t->jam_keluar ? 'Sudah keluar' : 'Masih di sekolah',
                    ]);
                });
        }

        return $data->sortByDesc('tanggal')->values();
    }
}
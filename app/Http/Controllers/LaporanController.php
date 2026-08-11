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
        $tanggal = $request->input('tanggal', Carbon::today()->toDateString());
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
            'preview' => $data->take(10)->values(),
            'params' => compact('jenis', 'periode', 'tanggal', 'semester'),
        ]);
    }

    public function excel(Request $request)
    {
        $jenis = $request->input('jenis', 'gabungan');
        $periode = $request->input('periode', 'harian');
        $tanggal = $request->input('tanggal', Carbon::today()->toDateString());
        $semester = $request->input('semester', 'ganjil');

        [$start, $end, $labelPeriode] = $this->hitungRentang($periode, $tanggal, $semester);
        $data = $this->ambilData($jenis, $start, $end);

        $namaFile = 'Laporan_' . ucfirst($jenis) . '_' . ucfirst($periode) . '_' . $start->isoFormat('D-MMM-Y') . '.xlsx';

        return Excel::download(new LaporanExport($data, $labelPeriode, $jenis), $namaFile);
    }

    // ===== METHOD PDF LENGKAP =====
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
            $tanggal  = $request->input('tanggal', now()->toDateString());
            $semester = $request->input('semester', 'ganjil');

            $tanggalRef = Carbon::parse($tanggal);

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

            $absensiPetugas = AbsensiPetugas::whereBetween('tanggal', [$dariStr, $sampaiStr])
                ->orderBy('tanggal')->orderBy('jam_masuk')->get();

            $hadirHariIni = AbsensiPetugas::where('tanggal', $sampaiStr)->count();
            $alphaHariIni = max(0, User::where('role', 'petugas')->count() - $hadirHariIni);

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

            $trend = Pelanggaran::select(DB::raw('DATE(tanggal) as tanggal'), DB::raw('COUNT(*) as jumlah'))
                ->whereBetween('tanggal', [$dariStr, $sampaiStr])
                ->groupBy('tanggal')->orderBy('tanggal')->get();

            $statusPelanggaran = Pelanggaran::select('status as label', DB::raw('COUNT(*) as jumlah'))
                ->whereBetween('tanggal', [$dariStr, $sampaiStr])
                ->groupBy('status')->orderByDesc('jumlah')->get();

            $jenisPelanggaran = Pelanggaran::select('jenis_pelanggaran as label', DB::raw('COUNT(*) as jumlah'))
                ->whereBetween('tanggal', [$dariStr, $sampaiStr])
                ->groupBy('jenis_pelanggaran')->orderByDesc('jumlah')->limit(10)->get();

            $aktivitas = collect();
            $keterlambatan->sortByDesc('created_at')->take(5)->each(fn ($k) => $aktivitas->push([
                'waktu' => $k->created_at?->toIsoString(), 'tipe' => 'Terlambat',
                'teks' => ($k->siswa?->nama ?? '-').' ('.($k->siswa?->kelas ?? '-').') terlambat '.$k->menit_terlambat.' menit',
            ]));
            $izinKeluar->sortByDesc('created_at')->take(5)->each(fn ($i) => $aktivitas->push([
                'waktu' => $i->created_at?->toIsoString(), 'tipe' => 'Izin Keluar',
                'teks' => ($i->siswa?->nama ?? '-').' ('.($i->siswa?->kelas ?? '-').') izin keluar: '.$i->jenis,
            ]));
            $pelanggaran->sortByDesc('created_at')->take(5)->each(fn ($p) => $aktivitas->push([
                'waktu' => $p->created_at?->toIsoString(), 'tipe' => 'Pelanggaran',
                'teks' => ($p->siswa?->nama ?? '-').' ('.($p->siswa?->kelas ?? '-').') '.$p->jenis_pelanggaran.' ('.$p->poin.' poin)',
            ]));
            $tamu->sortByDesc('created_at')->take(5)->each(fn ($t) => $aktivitas->push([
                'waktu' => $t->created_at?->toIsoString(), 'tipe' => 'Tamu',
                'teks' => $t->nama.' ('.($t->instansi ?: 'Umum').') — '.$t->keperluan,
            ]));
            $aktivitas = $aktivitas->sortByDesc('waktu')->take(15)->values();

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

        // Logo instansi (base64)
        $logoInstansi = null;
        if ($pengaturan?->logo_instansi) {
            $path = storage_path('app/public/'.$pengaturan->logo_instansi);
            if (file_exists($path)) {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
            $logoInstansi = 'data:image/'.$ext.';base64,'.base64_encode(file_get_contents($path));
        }
    }
}
            $logoInstansi = null;
            if ($pengaturan?->logo_instansi) {
                $path = storage_path('app/public/'.$pengaturan->logo_instansi);
                if (file_exists($path)) {
                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
                        $logoInstansi = 'data:image/'.$ext.';base64,'.base64_encode(file_get_contents($path));
                    }
                }
            }

            $totalData = $absensiPetugas->count()
                       + $keterlambatan->count()
                       + $izinKeluar->count()
                       + $pelanggaran->count()
                       + $tamu->count();

            $data = [
                'pengaturan' => $pengaturan,
                'logoInstansi' => $logoInstansi,
                'logo' => $logo,
                'labelPeriode' => $labelPeriode,
                'absensiPetugas' => $absensiPetugas,
                'ringkasan' => $ringkasan,
                'keterlambatan' => $keterlambatan,
                'izinKeluar' => $izinKeluar,
                'pelanggaran' => $pelanggaran,
                'tamu' => $tamu,
                'perKelas' => $perKelas,
                'perJurusan' => $perJurusan,
                'trend' => $trend,
                'statusPelanggaran' => $statusPelanggaran,
                'jenisPelanggaran' => $jenisPelanggaran,
                'aktivitas' => $aktivitas,
                'topPoin' => $topPoin,
                'topTerlambat' => $topTerlambat,
                'totalData' => $totalData,
                'dicetakOleh' => auth()->user()?->name ?? 'Sistem Otomatis',
                'waktuCetak' => now()->format('d-m-Y H:i'),
            ];

            $pdf = Pdf::loadView('laporan.pdf', $data)->setPaper('a4', 'portrait');
            return $pdf->download('Laporan-Piket-'.$periode.'-'.$dariStr.'.pdf');

        } catch (\Throwable $e) {
            Log::error('PDF Error: '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
            return response()->json([
                'error' => 'Gagal generate PDF: '.$e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    private function hitungRentang(string $periode, string $tanggal, string $semester): array
    {
        $date = Carbon::parse($tanggal);

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
<?php

namespace App\Http\Controllers;

use App\Models\AbsensiPetugas;
use App\Models\BukuTamu;
use App\Models\IzinKeluar;
use App\Models\Keterlambatan;
use App\Models\Pelanggaran;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

        // 🔒 KUNCI: TV SELALU menampilkan data HARI INI saja
        $request->merge([
            'dari_tanggal'   => Carbon::today()->toDateString(),
            'sampai_tanggal' => Carbon::today()->toDateString(),
        ]);

        return Inertia::render('Tampil', $this->buildData($request) + [
            'displayKey' => config('services.display.key'),
        ]);
    }

    private function buildData(Request $request): array
    {
        // ===== Filter rentang tanggal =====
        $dariTanggal = $request->input('dari_tanggal', Carbon::today()->toDateString());
        $sampaiTanggal = $request->input('sampai_tanggal', Carbon::today()->toDateString());

        // ===== Periode grafik pelanggaran =====
        $periodeGrafik = $request->input('periode_grafik', '7');
        $hariGrafik = in_array($periodeGrafik, ['7', '14', '30']) ? (int) $periodeGrafik : 7;
        $awalGrafik = Carbon::today()->subDays($hariGrafik - 1);

        // ===== Filter khusus grafik keterlambatan =====
        $grafikKelas = $request->input('grafik_kelas');
        $grafikJurusan = $request->input('grafik_jurusan');

        // ===== Kartu Statistik =====
        $stats = [
            'total_siswa' => Siswa::where('aktif', true)->count(),
            'terlambat' => Keterlambatan::whereBetween('tanggal', [$dariTanggal, $sampaiTanggal])->count(),
            'izin_keluar' => IzinKeluar::whereBetween('tanggal', [$dariTanggal, $sampaiTanggal])->count(),
            'pelanggaran' => Pelanggaran::whereDate('tanggal', '>=', $dariTanggal) ->whereDate('tanggal', '<=', $sampaiTanggal)->count(),
            'tamu' => BukuTamu::whereBetween('tanggal_kunjungan', [$dariTanggal, $sampaiTanggal])->count(),
            'tamu_masih_di_sekolah' => BukuTamu::whereDate('tanggal_kunjungan', Carbon::today())
                ->whereNull('jam_keluar')->count(),
        ];

        // ===== GRAFIK KETERLAMBATAN PER KELAS =====
        $chartData = Keterlambatan::select('siswa.kelas as label', DB::raw('COUNT(*) as jumlah'))
            ->join('siswa', 'siswa.id', '=', 'keterlambatan.siswa_id')
            ->whereBetween('keterlambatan.tanggal', [$dariTanggal, $sampaiTanggal])
            ->when($grafikKelas, fn ($q) => $q->where('siswa.kelas', $grafikKelas))
            ->when($grafikJurusan, fn ($q) => $q->where('siswa.jurusan', $grafikJurusan))
            ->groupBy('siswa.kelas')
            ->orderByDesc('jumlah')
            ->get()
            ->map(fn ($d) => ['label' => $d->label, 'jumlah' => (int) $d->jumlah])
            ->values();

        // ===== GRAFIK PELANGGARAN PER HARI =====
        $pelanggaranGrafik = Pelanggaran::select(
                DB::raw('DATE(tanggal) as tanggal'), DB::raw('COUNT(*) as jumlah'))
            ->whereDate('tanggal', '>=', $awalGrafik)
            ->groupBy('tanggal')->orderBy('tanggal')->get()->keyBy('tanggal');

        $chartPelanggaran = [];
        for ($i = $hariGrafik - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartPelanggaran[] = [
                'label' => $hariGrafik > 14 ? $date->isoFormat('D/M') : $date->isoFormat('ddd'),
                'title' => $date->isoFormat('ddd, D MMM'),
                'jumlah' => $pelanggaranGrafik[$date->format('Y-m-d')]->jumlah ?? 0,
            ];
        }

        // ===== DONUT 1: Keterlambatan per Jurusan =====
        $donutJurusan = Keterlambatan::select('siswa.jurusan as label', DB::raw('COUNT(*) as jumlah'))
            ->join('siswa', 'siswa.id', '=', 'keterlambatan.siswa_id')
            ->whereBetween('keterlambatan.tanggal', [$dariTanggal, $sampaiTanggal])
            ->groupBy('siswa.jurusan')->orderByDesc('jumlah')->get()
            ->map(fn ($d) => ['label' => $d->label ?: 'Tanpa Jurusan', 'jumlah' => (int) $d->jumlah]);

        // ===== DONUT 2: Status Pelanggaran =====
        $donutStatusPelanggaran = Pelanggaran::select('status as label', DB::raw('COUNT(*) as jumlah'))
            ->whereBetween('tanggal', [$dariTanggal, $sampaiTanggal])
            ->groupBy('status')->orderByDesc('jumlah')->get()
            ->map(fn ($d) => ['label' => ucfirst($d->label ?? 'Tanpa Status'), 'jumlah' => (int) $d->jumlah]);

        // ===== Jenis pelanggaran =====
        $jenisPelanggaran = Pelanggaran::select('jenis_pelanggaran', DB::raw('COUNT(*) as jumlah'))
            ->whereBetween('tanggal', [$dariTanggal, $sampaiTanggal])
            ->groupBy('jenis_pelanggaran')->orderByDesc('jumlah')->limit(5)->get()
            ->map(fn ($d) => ['label' => $d->jenis_pelanggaran, 'jumlah' => (int) $d->jumlah]);

        // ===== Top siswa =====
        $topPelanggaran = Pelanggaran::select('siswa_id',
                DB::raw('SUM(poin) as total_poin'), DB::raw('COUNT(*) as jumlah_kasus'))
            ->with('siswa:id,nisn,nama,kelas')
            ->whereBetween('tanggal', [$dariTanggal, $sampaiTanggal])
            ->groupBy('siswa_id')->orderByDesc('total_poin')->limit(5)->get()
            ->map(fn ($p) => [
                'nisn' => $p->siswa?->nisn ?? '-',
                'nama' => $p->siswa?->nama ?? '-',
                'kelas' => $p->siswa?->kelas ?? '-',
                'total_poin' => (int) ($p->total_poin ?? 0),
                'jumlah_kasus' => (int) ($p->jumlah_kasus ?? 0),
            ]);

        $topTerlambat = Keterlambatan::select('siswa_id',
                DB::raw('COUNT(*) as jumlah'), DB::raw('AVG(menit_terlambat) as rata_menit'))
            ->with('siswa:id,nisn,nama,kelas')
            ->whereBetween('tanggal', [$dariTanggal, $sampaiTanggal])
            ->groupBy('siswa_id')->orderByDesc('jumlah')->limit(5)->get()
            ->map(fn ($k) => [
                'nisn' => $k->siswa?->nisn ?? '-',
                'nama' => $k->siswa?->nama ?? '-',
                'kelas' => $k->siswa?->kelas ?? '-',
                'jumlah' => (int) ($k->jumlah ?? 0),
                'rata_menit' => round((float) ($k->rata_menit ?? 0), 1),
            ]);

                        // ===== KELAS MELANGGAR TERTINGGI (dalam rentang filter) =====
            $kelasMelanggarTertinggi = Pelanggaran::select(
                    'siswa.kelas as kelas',
                    DB::raw('COUNT(*) as jumlah'),
                    DB::raw('SUM(pelanggaran.poin) as total_poin')
                )
                ->join('siswa', 'siswa.id', '=', 'pelanggaran.siswa_id')
                ->whereDate('pelanggaran.tanggal', '>=', $dariTanggal)
                ->whereDate('pelanggaran.tanggal', '<=', $sampaiTanggal)
                ->groupBy('siswa.kelas')
                ->orderByDesc('jumlah')
                ->limit(5)
                ->get()
                ->map(fn ($k) => [
                    'kelas'      => $k->kelas ?? 'Tanpa Kelas',
                    'jumlah'     => (int) $k->jumlah,
                    'total_poin' => (int) ($k->total_poin ?? 0),
                ]);

        // ===== Aktivitas Terbaru =====
        $aktivitas = collect();

        Keterlambatan::with('siswa:id,nama,kelas')->latest()->take(5)->get()->each(
            fn ($k) => $aktivitas->push([
                'waktu' => $k->created_at?->toIsoString(),
                'tipe' => 'Terlambat', 'warna' => 'red',
                'teks' => ($k->siswa?->nama ?? '-').' ('.($k->siswa?->kelas ?? '-').') terlambat '.($k->menit_terlambat ?? 0).' menit',
            ])
        );
        IzinKeluar::with('siswa:id,nama,kelas')->latest()->take(5)->get()->each(
            fn ($i) => $aktivitas->push([
                'waktu' => $i->created_at?->toIsoString(),
                'tipe' => 'Izin Keluar', 'warna' => 'yellow',
                'teks' => ($i->siswa?->nama ?? '-').' ('.($i->siswa?->kelas ?? '-').') izin keluar: '.($i->jenis ?? '-'),
            ])
        );
        Pelanggaran::with('siswa:id,nama,kelas')->latest()->take(5)->get()->each(
            fn ($p) => $aktivitas->push([
                'waktu' => $p->created_at?->toIsoString(),
                'tipe' => 'Pelanggaran', 'warna' => 'orange',
                'teks' => ($p->siswa?->nama ?? '-').' ('.($p->siswa?->kelas ?? '-').') '.($p->jenis_pelanggaran ?? '-').' ('.($p->poin ?? 0).' poin)',
            ])
        );
        BukuTamu::latest()->take(5)->get()->each(
            fn ($t) => $aktivitas->push([
                'waktu' => $t->created_at?->toIsoString(),
                'tipe' => 'Tamu', 'warna' => 'blue',
                'teks' => $t->nama.' ('.($t->instansi ?: 'Umum').') — '.($t->keperluan ?? '-'),
            ])
        );

        $aktivitas = $aktivitas->sortByDesc('waktu')->take(8)->values();

        // ===== Opsi filter =====
        $kelasOptions = Siswa::distinct()->orderBy('kelas')->pluck('kelas')->filter()->values();
        $jurusanOptions = Siswa::distinct()->orderBy('jurusan')->pluck('jurusan')->filter()->values();

        $rentangAktif = Carbon::parse($dariTanggal)->isoFormat('D MMM Y')
            .' — '.Carbon::parse($sampaiTanggal)->isoFormat('D MMM Y');

        $key = config('services.display.key');
        $tampilUrl = route('tampil', $key ? ['k' => $key] : []);

        // ===== ABSENSI PETUGAS + ALPHA OTOMATIS =====
        $tanggalHariIni = Carbon::today()->toDateString();
        $jamSekarang    = now()->format('H:i');
        $batasAlpha     = '08:30'; // setelah jam ini, petugas belum absen = ALPHA

        // 1. Petugas yang SUDAH absen hari ini
        $absensiTercatat = AbsensiPetugas::where('tanggal', $tanggalHariIni)
            ->orderBy('jam_masuk')->get()
            ->map(fn ($a) => [
                'nama'    => $a->nama,
                'jabatan' => $a->jabatan,
                'jam'     => $a->jam_masuk ? substr($a->jam_masuk, 0, 5) : null,
                'status'  => $a->status,
            ])
            ->values()
            ->toBase();   // ← FIX: ubah ke Support Collection (aman di-merge)

        // 2. Petugas yang BELUM absen → jadi ALPHA (jika jam sekarang >= 08:00)
        $alphaList = collect();
        if ($jamSekarang >= $batasAlpha) {
            $namaSudahAbsen = $absensiTercatat->pluck('nama')->toArray();

            $alphaList = User::where('role', 'petugas')
                ->whereNotIn('name', $namaSudahAbsen)
                ->orderBy('name')
                ->get()
                ->map(fn ($u) => [
                    'nama'    => $u->name,
                    'jabatan' => 'Petugas Piket',
                    'jam'     => null,
                    'status'  => 'alpha',
                ])
                ->values()
                ->toBase();   // ← FIX: ubah ke Support Collection (aman di-merge)
        }

        // 3. Gabungkan: hadir dulu (urut jam), lalu alpha
        //    Sekarang AMAN karena keduanya Support Collection (bukan Eloquent)
        $absensiPetugas = $absensiTercatat->merge($alphaList)->values();

        return [
            'stats' => $stats,
            'absensiPetugas' => $absensiPetugas,
            'chartData' => $chartData,
            'chartPelanggaran' => $chartPelanggaran,
            'donutJurusan' => $donutJurusan,
            'donutStatusPelanggaran' => $donutStatusPelanggaran,
            'jenisPelanggaran' => $jenisPelanggaran,
            'kelasMelanggarTertinggi' => $kelasMelanggarTertinggi,  
            'topPelanggaran' => $topPelanggaran,
            'topTerlambat' => $topTerlambat,
            'aktivitas' => $aktivitas,
            'kelasOptions' => $kelasOptions,
            'jurusanOptions' => $jurusanOptions,
            'rentangAktif' => $rentangAktif,
            'hariIni' => Carbon::today()->isoFormat('dddd, D MMMM Y'),
            'tampilUrl' => $tampilUrl,
            'params' => [
                'dari_tanggal' => $dariTanggal,
                'sampai_tanggal' => $sampaiTanggal,
                'periode_grafik' => (string) $periodeGrafik,
                'grafik_kelas' => $grafikKelas,
                'grafik_jurusan' => $grafikJurusan,
            ],
        ];
    }
}
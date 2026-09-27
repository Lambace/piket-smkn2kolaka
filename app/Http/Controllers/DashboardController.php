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

        // ===== 1. FILTER RENTANG TANGGAL =====
        $dariTanggal = $request->input('dari_tanggal', Carbon::today()->toDateString());
        $sampaiTanggal = $request->input('sampai_tanggal', Carbon::today()->toDateString());
        
        $rangeStart = Carbon::parse($dariTanggal, 'Asia/Makassar')->startOfDay();
        $rangeEnd   = Carbon::parse($sampaiTanggal, 'Asia/Makassar')->endOfDay();

        $withSiswa = 'siswa:id,nama,kelas,jurusan,nisn';

        // ===== 2. DATA LIST (Sesuai nama props di Tampil.jsx) =====
        $keterlambatanList = \App\Models\Keterlambatan::with($withSiswa)
            ->whereBetween('tanggal', [$rangeStart, $rangeEnd])
            ->orderByDesc('tanggal')->orderByDesc('jam_datang')->limit(50)->get()
            ->map(fn ($k) => [
                'id' => $k->id, 'nama' => $k->siswa?->nama, 'kelas' => $k->siswa?->kelas, 
                'tanggal' => $k->tanggal->format('d/m/Y'), 'jam_datang' => $k->jam_datang, 
                'menit_terlambat' => (int) $k->menit_terlambat, 'status' => $k->status,
            ]);

        $izinKeluarList = \App\Models\IzinKeluar::with($withSiswa)
            ->whereBetween('tanggal', [$rangeStart, $rangeEnd])
            ->orderByDesc('tanggal')->orderByDesc('jam_keluar')->limit(50)->get()
            ->map(fn ($i) => [
                'id' => $i->id, 'nama' => $i->siswa?->nama, 'kelas' => $i->siswa?->kelas, 
                'tanggal' => $i->tanggal->format('d/m/Y'), 'jam_keluar' => $i->jam_keluar, 
                'jam_kembali' => $i->jam_kembali, 'jenis' => $i->jenis, 'status' => $i->status,
            ]);

        $pelanggaranList = \App\Models\Pelanggaran::with($withSiswa)
            ->whereBetween('tanggal', [$rangeStart, $rangeEnd])
            ->orderByDesc('tanggal')->orderByDesc('created_at')->limit(50)->get()
            ->map(fn ($p) => [
                'id' => $p->id, 'nama' => $p->siswa?->nama, 'kelas' => $p->siswa?->kelas, 
                'tanggal' => $p->tanggal->format('d/m/Y'), 'jenis_pelanggaran' => $p->jenis_pelanggaran, 
                'poin' => (int) $p->poin, 'status' => $p->status,
            ]);

        $bukuTamuList = \App\Models\BukuTamu::whereBetween('tanggal_kunjungan', [$rangeStart, $rangeEnd])
            ->orderByDesc('tanggal_kunjungan')->orderByDesc('jam_masuk')->limit(50)->get()
            ->map(fn ($t) => [
                'id' => $t->id, 'nama' => $t->nama, 'instansi' => $t->instansi, 
                'tanggal' => $t->tanggal_kunjungan->format('d/m/Y'), 'jam_masuk' => $t->jam_masuk, 
                'jam_keluar' => $t->jam_keluar, 'status' => $t->jam_keluar ? 'sudah_keluar' : 'di_sekolah',
            ]);

        // ===== 3. STATISTIK (Wajib ada untuk KartuStatistik) =====
        $stats = [
            'total_siswa' => \App\Models\Siswa::where('aktif', true)->count(),
            'terlambat'   => \App\Models\Keterlambatan::whereBetween('tanggal', [$rangeStart, $rangeEnd])->count(),
            'izin_keluar' => \App\Models\IzinKeluar::whereBetween('tanggal', [$rangeStart, $rangeEnd])->count(),
            'pelanggaran' => \App\Models\Pelanggaran::whereBetween('tanggal', [$rangeStart, $rangeEnd])->count(),
            'tamu'        => \App\Models\BukuTamu::whereBetween('tanggal_kunjungan', [$rangeStart, $rangeEnd])->count(),
        ];

        // ===== 4. ABSENSI PETUGAS (Wajib ada untuk KartuAbsensiPetugas) =====
        $hariIniStr = Carbon::today()->toDateString();
        $absensiTercatat = \App\Models\AbsensiPetugas::where('tanggal', $hariIniStr)
            ->orderBy('jam_masuk')->get()->map(fn ($a) => [
                'nama' => $a->nama, 'jabatan' => $a->jabatan, 
                'jam' => $a->jam_masuk ? substr($a->jam_masuk, 0, 5) : null, 'status' => $a->status,
            ])->values()->toBase();
        
        $absensiPetugas = $absensiTercatat; // Disederhanakan untuk live view

        // ===== 5. GRAFIK & CHART (Nama props harus persis sama dengan Tampil.jsx) =====
        // chartData (Keterlambatan per Kelas)
        $chartData = \App\Models\Keterlambatan::select('siswa.kelas as label', DB::raw('COUNT(*) as jumlah'))
            ->join('siswa', 'siswa.id', '=', 'keterlambatan.siswa_id')
            ->whereBetween('keterlambatan.tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa.kelas')->orderByDesc('jumlah')->get()
            ->map(fn ($d) => ['label' => $d->label, 'jumlah' => (int) $d->jumlah])->values();

        // donutJurusan (Keterlambatan per Jurusan)
        $donutJurusan = \App\Models\Keterlambatan::select('siswa.jurusan as label', DB::raw('COUNT(*) as jumlah'))
            ->join('siswa', 'siswa.id', '=', 'keterlambatan.siswa_id')
            ->whereBetween('keterlambatan.tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa.jurusan')->orderByDesc('jumlah')->get()
            ->map(fn ($d) => ['label' => $d->label ?: 'Tanpa Jurusan', 'jumlah' => (int) $d->jumlah]);

        // chartIzinKelas & donutIzinJurusan
        $chartIzinKelas = \App\Models\IzinKeluar::select('siswa.kelas as label', DB::raw('COUNT(*) as jumlah'))
            ->join('siswa', 'siswa.id', '=', 'izin_keluar.siswa_id')
            ->whereBetween('izin_keluar.tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa.kelas')->orderByDesc('jumlah')->get()
            ->map(fn ($d) => ['label' => $d->label ?? 'Tanpa Kelas', 'jumlah' => (int) $d->jumlah])->values();

        $donutIzinJurusan = \App\Models\IzinKeluar::select('siswa.jurusan as label', DB::raw('COUNT(*) as jumlah'))
            ->join('siswa', 'siswa.id', '=', 'izin_keluar.siswa_id')
            ->whereBetween('izin_keluar.tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa.jurusan')->orderByDesc('jumlah')->get()
            ->map(fn ($d) => ['label' => $d->label ?: 'Tanpa Jurusan', 'jumlah' => (int) $d->jumlah]);

        // chartPelanggaranKelas & donutPelanggaranJurusan
        $chartPelanggaranKelas = \App\Models\Pelanggaran::select('siswa.kelas as label', DB::raw('COUNT(*) as jumlah'))
            ->join('siswa', 'siswa.id', '=', 'pelanggaran.siswa_id')
            ->whereBetween('pelanggaran.tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa.kelas')->orderByDesc('jumlah')->get()
            ->map(fn ($d) => ['label' => $d->label ?? 'Tanpa Kelas', 'jumlah' => (int) $d->jumlah])->values();

        $donutPelanggaranJurusan = \App\Models\Pelanggaran::select('siswa.jurusan as label', DB::raw('COUNT(*) as jumlah'))
            ->join('siswa', 'siswa.id', '=', 'pelanggaran.siswa_id')
            ->whereBetween('pelanggaran.tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa.jurusan')->orderByDesc('jumlah')->get()
            ->map(fn ($d) => ['label' => $d->label ?: 'Tanpa Jurusan', 'jumlah' => (int) $d->jumlah]);

        // ===== 6. TABEL RANKING & AKTIVITAS =====
        // topTerlambat
        $topTerlambat = \App\Models\Keterlambatan::select('siswa_id', DB::raw('COUNT(*) as jumlah'), DB::raw('AVG(menit_terlambat) as rata_menit'))
            ->with('siswa:id,nisn,nama,kelas')->whereBetween('tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa_id')->orderByDesc('jumlah')->limit(5)->get()
            ->map(fn ($k) => ['nisn' => $k->siswa?->nisn, 'nama' => $k->siswa?->nama, 'kelas' => $k->siswa?->kelas, 'jumlah' => (int) $k->jumlah, 'rata_menit' => round((float) $k->rata_menit, 1)]);

        // topPelanggaran (Wajib pakai nama ini agar cocok dengan Tampil.jsx)
        $topPelanggaran = \App\Models\Pelanggaran::select('siswa_id', DB::raw('SUM(poin) as total_poin'), DB::raw('COUNT(*) as jumlah_kasus'))
            ->with('siswa:id,nisn,nama,kelas')->whereBetween('tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa_id')->orderByDesc('total_poin')->limit(5)->get()
            ->map(fn ($p) => ['nisn' => $p->siswa?->nisn, 'nama' => $p->siswa?->nama, 'kelas' => $p->siswa?->kelas, 'total_poin' => (int) $p->total_poin, 'jumlah_kasus' => (int) $p->jumlah_kasus]);

        // aktivitas
        $aktivitas = collect();
        \App\Models\Keterlambatan::with('siswa:id,nama,kelas')->whereBetween('tanggal', [$rangeStart, $rangeEnd])->latest()->take(3)->get()->each(fn ($k) => $aktivitas->push(['waktu' => $k->created_at?->toIsoString(), 'tipe' => 'Terlambat', 'warna' => 'red', 'teks' => ($k->siswa?->nama ?? '-').' terlambat '.($k->menit_terlambat ?? 0).' menit']));
        \App\Models\Pelanggaran::with('siswa:id,nama,kelas')->whereBetween('tanggal', [$rangeStart, $rangeEnd])->latest()->take(3)->get()->each(fn ($p) => $aktivitas->push(['waktu' => $p->created_at?->toIsoString(), 'tipe' => 'Pelanggaran', 'warna' => 'orange', 'teks' => ($p->siswa?->nama ?? '-').' - '.($p->jenis_pelanggaran ?? '-')]));
        $aktivitas = $aktivitas->sortByDesc('waktu')->take(8)->values();

        $pengaturan = \App\Models\Pengaturan::first();

        // ===== 7. KIRIM KE FRONTEND DENGAN NAMA PROPS YANG PERSIS =====
        return Inertia::render('Tampil', [
            'hariIni'             => Carbon::now('Asia/Makassar')->isoFormat('dddd, D MMMM Y'),
            'stats'               => $stats,
            'absensiPetugas'      => $absensiPetugas,
            'displayKey'          => $key,
            'keterlambatanList'   => $keterlambatanList,
            'izinKeluarList'      => $izinKeluarList,
            'pelanggaranList'     => $pelanggaranList,
            'bukuTamuList'        => $bukuTamuList,
            
            // Nama variabel ini HARUS sama persis dengan yang dipakai di Tampil.jsx
            'chartData'                 => $chartData, 
            'donutJurusan'              => $donutJurusan,
            'chartIzinKelas'            => $chartIzinKelas,
            'donutIzinJurusan'          => $donutIzinJurusan,
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
        // ===== Filter rentang tanggal =====
        $dariTanggal = $request->input('dari_tanggal', Carbon::today()->startOfMonth()->toDateString());
        $sampaiTanggal = $request->input('sampai_tanggal', Carbon::today()->toDateString());

        // ===== Range Carbon (timezone-aware) =====
        $rangeStart = Carbon::parse($dariTanggal)->startOfDay();
        $rangeEnd   = Carbon::parse($sampaiTanggal)->endOfDay();

        // ===== Periode grafik pelanggaran =====
        $periodeGrafik = $request->input('periode_grafik', '7');
        $hariGrafik = in_array($periodeGrafik, ['7', '14', '30']) ? (int) $periodeGrafik : 7;

        // ===== Filter khusus grafik keterlambatan =====
        $grafikKelas = $request->input('grafik_kelas');
        $grafikJurusan = $request->input('grafik_jurusan');

        // ===== Kartu Statistik =====
        $stats = [
            'total_siswa' => Siswa::where('aktif', true)->count(),
            'terlambat'   => Keterlambatan::whereBetween('tanggal', [$rangeStart, $rangeEnd])->count(),
            'izin_keluar' => IzinKeluar::whereBetween('tanggal', [$rangeStart, $rangeEnd])->count(),
            'pelanggaran' => Pelanggaran::whereBetween('tanggal', [$rangeStart, $rangeEnd])->count(),
            'tamu'        => BukuTamu::whereBetween('tanggal_kunjungan', [$rangeStart, $rangeEnd])->count(),
            'tamu_masih_di_sekolah' => BukuTamu::whereDate('tanggal_kunjungan', Carbon::today())
                ->whereNull('jam_keluar')->count(),
        ];

        // ===== GRAFIK KETERLAMBATAN PER KELAS =====
        $chartData = Keterlambatan::select('siswa.kelas as label', DB::raw('COUNT(*) as jumlah'))
            ->join('siswa', 'siswa.id', '=', 'keterlambatan.siswa_id')
            ->whereBetween('keterlambatan.tanggal', [$rangeStart, $rangeEnd])
            ->when($grafikKelas, fn ($q) => $q->where('siswa.kelas', $grafikKelas))
            ->when($grafikJurusan, fn ($q) => $q->where('siswa.jurusan', $grafikJurusan))
            ->groupBy('siswa.kelas')
            ->orderByDesc('jumlah')
            ->get()
            ->map(fn ($d) => ['label' => $d->label, 'jumlah' => (int) $d->jumlah])
            ->values();

        // ===== GRAFIK PELANGGARAN PER HARI =====
        $chartPelanggaran = [];
        for ($i = $hariGrafik - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $jumlahHariIni = Pelanggaran::whereBetween('tanggal', [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
            ])->count();

            $chartPelanggaran[] = [
                'label'  => $hariGrafik > 14 ? $date->isoFormat('D/M') : $date->isoFormat('ddd'),
                'title'  => $date->isoFormat('ddd, D MMM'),
                'jumlah' => $jumlahHariIni,
            ];
        }

        // ===== DONUT 1: Keterlambatan per Jurusan =====
        $donutJurusan = Keterlambatan::select('siswa.jurusan as label', DB::raw('COUNT(*) as jumlah'))
            ->join('siswa', 'siswa.id', '=', 'keterlambatan.siswa_id')
            ->whereBetween('keterlambatan.tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa.jurusan')->orderByDesc('jumlah')->get()
            ->map(fn ($d) => ['label' => $d->label ?: 'Tanpa Jurusan', 'jumlah' => (int) $d->jumlah]);

        // ===== DONUT 2: Status Pelanggaran =====
        $donutStatusPelanggaran = Pelanggaran::select('status as label', DB::raw('COUNT(*) as jumlah'))
            ->whereBetween('tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('status')->orderByDesc('jumlah')->get()
            ->map(fn ($d) => ['label' => ucfirst($d->label ?? 'Tanpa Status'), 'jumlah' => (int) $d->jumlah]);

        // ===== Jenis pelanggaran =====
        $jenisPelanggaran = Pelanggaran::select('jenis_pelanggaran', DB::raw('COUNT(*) as jumlah'))
            ->whereBetween('tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('jenis_pelanggaran')->orderByDesc('jumlah')->limit(5)->get()
            ->map(fn ($d) => ['label' => $d->jenis_pelanggaran, 'jumlah' => (int) $d->jumlah]);

        // ===== Top siswa =====
        $topPelanggaran = Pelanggaran::select(
                'siswa_id',
                DB::raw('SUM(poin) as total_poin'),
                DB::raw('COUNT(*) as jumlah_kasus')
            )
            ->with('siswa:id,nisn,nama,kelas')
            ->whereBetween('tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa_id')->orderByDesc('total_poin')->limit(5)->get()
            ->map(fn ($p) => [
                'nisn'         => $p->siswa?->nisn ?? '-',
                'nama'         => $p->siswa?->nama ?? '-',
                'kelas'        => $p->siswa?->kelas ?? '-',
                'total_poin'   => (int) ($p->total_poin ?? 0),
                'jumlah_kasus' => (int) ($p->jumlah_kasus ?? 0),
            ]);

        $topTerlambat = Keterlambatan::select(
                'siswa_id',
                DB::raw('COUNT(*) as jumlah'),
                DB::raw('AVG(menit_terlambat) as rata_menit')
            )
            ->with('siswa:id,nisn,nama,kelas')
            ->whereBetween('tanggal', [$rangeStart, $rangeEnd])
            ->groupBy('siswa_id')->orderByDesc('jumlah')->limit(5)->get()
            ->map(fn ($k) => [
                'nisn'       => $k->siswa?->nisn ?? '-',
                'nama'       => $k->siswa?->nama ?? '-',
                'kelas'      => $k->siswa?->kelas ?? '-',
                'jumlah'     => (int) ($k->jumlah ?? 0),
                'rata_menit' => round((float) ($k->rata_menit ?? 0), 1),
            ]);

        // ✅ PERBAIKAN 3, 4, 5: Mengubah 'pelanggarans' menjadi 'pelanggaran' di query Kelas Melanggar Tertinggi
        $kelasMelanggarTertinggi = Pelanggaran::select(
                'siswa.kelas as kelas',
                DB::raw('COUNT(*) as jumlah'),
                DB::raw('SUM(pelanggaran.poin) as total_poin') 
            )
            ->join('siswa', 'siswa.id', '=', 'pelanggaran.siswa_id') 
            ->whereBetween('pelanggaran.tanggal', [$rangeStart, $rangeEnd]) 
            ->groupBy('siswa.kelas')
            ->orderByDesc('jumlah')
            ->limit(5)
            ->get()
            ->map(fn ($k) => [
                'kelas'      => $k->kelas ?? 'Tanpa Kelas',
                'jumlah'     => (int) $k->jumlah,
                'total_poin' => (int) ($k->total_poin ?? 0),
            ]);

        // ==========================================
        // DATA KETERLAMBATAN HARIAN (Tabel Dashboard)
        // ==========================================
        $tanggalTarget = $rangeStart->toDateString(); 
        
        $keterlambatanHarian = Keterlambatan::with('siswa:id,nama,kelas,nisn')
            ->whereDate('tanggal', $tanggalTarget)
            ->orderByDesc('jam_datang')
            ->get()
            ->map(fn ($k) => [
                'id'              => $k->id,
                'nama'            => $k->siswa?->nama ?? '-',
                'kelas'           => $k->siswa?->kelas ?? '-',
                'nisn'            => $k->siswa?->nisn ?? '-',
                'tanggal'         => $k->tanggal,
                'jam_datang'      => $k->jam_datang,
                'menit_terlambat' => (int) $k->menit_terlambat,
                'status'          => $k->status,
            ]);

        // ===== Aktivitas Terbaru =====
        $aktivitas = collect();

        Keterlambatan::with('siswa:id,nama,kelas')->latest()->take(5)->get()->each(
            fn ($k) => $aktivitas->push([
                'waktu'  => $k->created_at?->toIsoString(),
                'tipe'   => 'Terlambat', 'warna' => 'red',
                'teks'   => ($k->siswa?->nama ?? '-').' ('.($k->siswa?->kelas ?? '-').') terlambat '.($k->menit_terlambat ?? 0).' menit',
            ])
        );
        IzinKeluar::with('siswa:id,nama,kelas')->latest()->take(5)->get()->each(
            fn ($i) => $aktivitas->push([
                'waktu'  => $i->created_at?->toIsoString(),
                'tipe'   => 'Izin Keluar', 'warna' => 'yellow',
                'teks'   => ($i->siswa?->nama ?? '-').' ('.($i->siswa?->kelas ?? '-').') izin keluar: '.($i->jenis ?? '-'),
            ])
        );
        Pelanggaran::with('siswa:id,nama,kelas')->latest()->take(5)->get()->each(
            fn ($p) => $aktivitas->push([
                'waktu'  => $p->created_at?->toIsoString(),
                'tipe'   => 'Pelanggaran', 'warna' => 'orange',
                'teks'   => ($p->siswa?->nama ?? '-').' ('.($p->siswa?->kelas ?? '-').') '.($p->jenis_pelanggaran ?? '-').' ('.($p->poin ?? 0).' poin)',
            ])
        );
        BukuTamu::latest()->take(5)->get()->each(
            fn ($t) => $aktivitas->push([
                'waktu'  => $t->created_at?->toIsoString(),
                'tipe'   => 'Tamu', 'warna' => 'blue',
                'teks'   => $t->nama.' ('.($t->instansi ?: 'Umum').') — '.($t->keperluan ?? '-'),
            ])
        );

        $aktivitas = $aktivitas->sortByDesc('waktu')->take(8)->values();

        // ===== Opsi filter =====
        $kelasOptions   = Siswa::distinct()->orderBy('kelas')->pluck('kelas')->filter()->values();
        $jurusanOptions = Siswa::distinct()->orderBy('jurusan')->pluck('jurusan')->filter()->values();

        $rentangAktif = Carbon::parse($dariTanggal)->isoFormat('D MMM Y')
            .' — '.Carbon::parse($sampaiTanggal)->isoFormat('D MMM Y');

        $key = config('services.display.key');
        $tampilUrl = route('tampil', $key ? ['k' => $key] : []);

                     // ===== 4. ABSENSI PETUGAS DENGAN LOGIKA JAM 07:30 =====
        $hariIniStr = Carbon::today('Asia/Makassar')->toDateString();
        $jamSekarang = Carbon::now('Asia/Makassar')->format('H:i');
        $batasAlpha = '07:30'; // Petugas baru dianggap Alpha setelah jam ini

        // 1. Ambil semua petugas dari tabel users
        $semuaPetugas = \App\Models\User::whereIn('role', ['petugas', 'koordinator'])
            ->orderBy('name')
            ->get();

        // 2. Ambil absensi HARI INI (pakai whereDate agar aman dari timezone)
        $absensiHariIni = \App\Models\AbsensiPetugas::whereDate('tanggal', $hariIniStr)->get();

        // 3. Peta absensi berdasarkan nama (lowercase & trim)
        $absensiMap = [];
        foreach ($absensiHariIni as $absen) {
            $namaKey = strtolower(trim($absen->nama));
            $absensiMap[$namaKey] = $absen;
        }

        $hadirList = collect();
        $alphaList = collect();
        $belumAbsenList = collect(); // ✅ BARU: Daftar petugas yang belum absen (sebelum 07:30)

        // 4. Cek setiap petugas
        foreach ($semuaPetugas as $petugas) {
            $namaKey = strtolower(trim($petugas->name));
            $jabatan = $petugas->role === 'koordinator' ? 'Koordinator Piket' : 'Guru Piket';

            if (isset($absensiMap[$namaKey])) {
                $absen = $absensiMap[$namaKey];
                $status = $absen->status;

                // Jika di database sudah tercatat alpha → masuk Alpha
                if ($status === 'alpha') {
                    $alphaList->push([
                        'nama'    => $petugas->name,
                        'jabatan' => $jabatan,
                        'jam'     => null,
                        'status'  => 'alpha',
                    ]);
                } 
                // Selain alpha (tepat_waktu, terlambat, sakit, izin, dl) → masuk Hadir
                else {
                    $hadirList->push([
                        'nama'    => $petugas->name,
                        'jabatan' => $jabatan,
                        'jam'     => $absen->jam_masuk ? substr($absen->jam_masuk, 0, 5) : null,
                        'status'  => $status,
                    ]);
                }
            } else {
                // TIDAK ADA record absensi hari ini
                // ✅ LOGIKA BARU: Cek apakah sudah lewat jam 07:30
                if ($jamSekarang >= $batasAlpha) {
                    // Sudah lewat 07:30 → otomatis Alpha
                    $alphaList->push([
                        'nama'    => $petugas->name,
                        'jabatan' => $jabatan,
                        'jam'     => null,
                        'status'  => 'alpha',
                    ]);
                } else {
                    // Belum 07:30 → masuk daftar "Belum Absen" (bukan Alpha)
                    $belumAbsenList->push([
                        'nama'    => $petugas->name,
                        'jabatan' => $jabatan,
                        'jam'     => null,
                        'status'  => 'belum_absen',
                    ]);
                }
            }
        }

        // Gabungkan: Hadir + Belum Absen + Alpha
        $absensiPetugas = $hadirList
            ->merge($belumAbsenList)
            ->merge($alphaList)
            ->values();
        // Gabungkan: Daftar Hadir dulu, baru Daftar Alpha
        $absensiPetugas = $hadirList->merge($alphaList)->values();

        return [
            'stats'                   => $stats,
            'absensiPetugas'          => $absensiPetugas,
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
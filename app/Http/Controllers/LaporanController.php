<?php

namespace App\Http\Controllers;

use App\Models\BukuTamu;
use App\Models\IzinKeluar;
use App\Models\Keterlambatan;
use App\Models\Pelanggaran;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

    public function pdf(Request $request)
    {
        if (! $request->user()) {
            $key = config('services.display.key');
            if ($key && $request->query('k') !== $key) {
                abort(403, 'Akses ditolak. Tautan tidak valid.');
            }
        }
        $jenis = $request->input('jenis', 'gabungan');
        $periode = $request->input('periode', 'harian');
        $tanggal = $request->input('tanggal', Carbon::today()->toDateString());
        $semester = $request->input('semester', 'ganjil');

        [$start, $end, $labelPeriode] = $this->hitungRentang($periode, $tanggal, $semester);
        $data = $this->ambilData($jenis, $start, $end);

        $pdf = Pdf::loadView('laporan.pdf', [
            'data' => $data,
            'labelPeriode' => $labelPeriode,
            'jenis' => $jenis,
            'start' => $start,
            'end' => $end,
            'tanggalCetak' => Carbon::now(),
        ]);

        $namaFile = 'Laporan_' . ucfirst($jenis) . '_' . ucfirst($periode) . '_' . $start->isoFormat('D-MMM-Y') . '.pdf';

        return $pdf->download($namaFile);
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

        // Keterlambatan
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

        // Izin Keluar
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

        // Pelanggaran
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

        // Tamu
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

        // Urutkan tanggal terbaru
        return $data->sortByDesc('tanggal')->values();
    }
}
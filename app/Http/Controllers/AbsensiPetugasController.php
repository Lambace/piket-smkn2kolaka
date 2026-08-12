<?php

namespace App\Http\Controllers;

use App\Models\AbsensiPetugas;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AbsensiPetugasController extends Controller
{
    // Batas status tepat waktu (sesuaikan kebutuhan)
    const BATAS_TEPAT_WAKTU = '07:30';

    public function index()
    {
        $user = auth()->user();
        $today = now()->toDateString();
        $awalBulan = now()->startOfMonth()->toDateString();

        $absenHariIni = AbsensiPetugas::where('tanggal', $today)
            ->where('nama', $user->name)->first();

        $summary = [
            'hadir' => AbsensiPetugas::where('nama', $user->name)
                ->whereIn('status', ['tepat_waktu', 'terlambat'])
                ->where('tanggal', '>=', $awalBulan)->count(),
            'izin' => AbsensiPetugas::where('nama', $user->name)
                ->where('status', 'izin')->where('tanggal', '>=', $awalBulan)->count(),
            'sakit' => AbsensiPetugas::where('nama', $user->name)
                ->where('status', 'sakit')->where('tanggal', '>=', $awalBulan)->count(),
            'dl' => AbsensiPetugas::where('nama', $user->name)
                ->where('status', 'dl')->where('tanggal', '>=', $awalBulan)->count(),
        ];

        $riwayat = AbsensiPetugas::where('nama', $user->name)
            ->orderByDesc('tanggal')->limit(10)->get();

        return Inertia::render('AbsensiPetugas', [
            'absenHariIni' => $absenHariIni,
            'summary' => $summary,
            'riwayat' => $riwayat,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|in:masuk,sakit,izin,dl,lainnya',
            'keterangan' => 'required_if:status,sakit,izin,dl,lainnya|nullable|string|max:500',
        ]);

        $user = auth()->user();
        $today = now()->toDateString();

        // Cegah absen 2x dalam sehari
        if (AbsensiPetugas::where('tanggal', $today)->where('nama', $user->name)->exists()) {
            return back()->with('error', 'Anda sudah absen hari ini.');
        }

        if ($validated['status'] === 'masuk') {
            // ✅ KLIK MASUK → langsung tersimpan, tanpa keterangan
            $jam = now()->format('H:i:s');
            $status = $jam <= self::BATAS_TEPAT_WAKTU.':00' ? 'tepat_waktu' : 'terlambat';
            $jamMasuk = $jam;
            $keterangan = null;
        } else {
            // ⚠️ DROPDOWN → wajib keterangan (sudah divalidasi required_if)
            $status = $validated['status'];
            $jamMasuk = null;
            $keterangan = $validated['keterangan'];
        }

        AbsensiPetugas::create([
            'nama' => $user->name,
            'jabatan' => $user->role === 'koordinator' ? 'Koordinator Piket' : 'Guru Piket',
            'tanggal' => $today,
            'jam_masuk' => $jamMasuk,
            'status' => $status,
            'keterangan' => $keterangan,
        ]);

        return back()->with('success', 'Absensi berhasil dicatat.');
    }

    public function destroy($id)
    {
        AbsensiPetugas::where('id', $id)->delete();
        return back()->with('success', 'Data absensi dihapus.');
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\AbsensiPetugas;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AbsensiPetugasController extends Controller
{
    const BATAS_TEPAT_WAKTU = '07:30';

    public function index()
    {
        $user = auth()->user();
        $today = now()->toDateString();
        $awalBulan = now()->startOfMonth()->toDateString();

        // Absen hari ini untuk user sendiri
        $absenHariIni = AbsensiPetugas::where('tanggal', $today)
            ->where('nama', $user->name)->first();

        // Summary bulanan user sendiri
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

        // ===== BARU: Daftar semua petugas + status absen hari ini =====
        $semuaPetugas = User::whereIn('role', ['petugas', 'koordinator'])
            ->orderBy('name')
            ->get()
            ->map(function ($u) use ($today) {
                $absen = AbsensiPetugas::where('nama', $u->name)
                    ->where('tanggal', $today)
                    ->first();

                return [
                    'id'             => $u->id,
                    'nama'           => $u->name,
                    'jabatan'        => $u->role === 'koordinator' ? 'Koordinator Piket' : 'Guru Piket',
                    'absen_id'       => $absen?->id,
                    'status'         => $absen?->status ?? 'alpha',
                    'jam_masuk'      => $absen?->jam_masuk,
                    'keterangan'     => $absen?->keterangan,
                    'sudah_absen'    => $absen !== null,
                ];
            });

        return Inertia::render('AbsensiPetugas', [
            'absenHariIni'  => $absenHariIni,
            'summary'       => $summary,
            'riwayat'       => $riwayat,
            'semuaPetugas'  => $semuaPetugas, // ← BARU
            'isKoordinator' => $user->isKoordinator(), // ← BARU
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

        if (AbsensiPetugas::where('tanggal', $today)->where('nama', $user->name)->exists()) {
            return back()->with('error', 'Anda sudah absen hari ini.');
        }

        if ($validated['status'] === 'masuk') {
            $jam = now()->format('H:i:s');
            $status = $jam <= self::BATAS_TEPAT_WAKTU.':00' ? 'tepat_waktu' : 'terlambat';
            $jamMasuk = $jam;
            $keterangan = null;
        } else {
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

    // ===== BARU: Update status absensi (untuk koordinator) =====
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $absen = AbsensiPetugas::findOrFail($id);

        // Validasi: hanya koordinator yang boleh edit absen orang lain
        if (!$user->isKoordinator() && $absen->nama !== $user->name) {
            return back()->with('error', 'Tidak punya izin mengubah data ini.');
        }

        $validated = $request->validate([
            'status' => 'required|in:tepat_waktu,terlambat,sakit,izin,dl,lainnya,alpha',
            'jam_masuk' => 'nullable|date_format:H:i',
            'keterangan' => 'nullable|string|max:500',
        ]);

        // Update data
        $absen->status = $validated['status'];
        $absen->jam_masuk = $validated['jam_masuk'] ? $validated['jam_masuk'] . ':00' : $absen->jam_masuk;
        $absen->keterangan = $validated['keterangan'];
        $absen->save();

        return back()->with('success', 'Data absensi berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $absen = AbsensiPetugas::findOrFail($id);

        // Validasi: hanya koordinator atau pemilik sendiri
        if (!$user->isKoordinator() && $absen->nama !== $user->name) {
            return back()->with('error', 'Tidak punya izin menghapus data ini.');
        }

        $absen->delete();
        return back()->with('success', 'Data absensi dihapus.');
    }
}
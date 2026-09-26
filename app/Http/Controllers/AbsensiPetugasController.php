<?php

namespace App\Http\Controllers;

use App\Models\AbsensiPetugas;
use App\Models\Pengaturan;
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

        // ===== BARU: ambil pengaturan geofencing =====
        $pengaturan = Pengaturan::first();
        $geofence = [
            'aktif' => (bool) ($pengaturan && $pengaturan->lat && $pengaturan->lng),
            'lat'   => $pengaturan->lat ?? null,
            'lng'   => $pengaturan->lng ?? null,
            'radius_meter' => (int) ($pengaturan->radius_meter ?? 150),
        ];
        // ===== AKHIR BARU =====

        // Redirect otomatis kalau petugas sudah absen
        if ($user->role !== 'koordinator') {
            $sudahAbsen = AbsensiPetugas::where('tanggal', $today)
                ->where('nama', $user->name)->exists();

            if ($sudahAbsen) {
                return redirect()->route('dashboard');
            }
        }

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

        $semuaPetugas = User::whereIn('role', ['petugas', 'koordinator'])
            ->orderBy('name')
            ->get()
            ->map(function ($u) use ($today) {
                $absen = AbsensiPetugas::where('nama', $u->name)
                    ->where('tanggal', $today)->first();

                return [
                    'id'             => $u->id,
                    'nama'           => $u->name,
                    'jabatan'        => $u->role === 'koordinator' ? 'Koordinator Piket' : 'Guru Piket',
                    'absen_id'       => $absen?->id,
                    'status'         => $absen?->status ?? 'alpha',
                    'jam_masuk'      => $absen?->jam_masuk,
                    'keterangan'     => $absen?->keterangan,
                    'sudah_absen'    => $absen !== null,
                    'jarak_meter'    => $absen?->jarak_meter,
                ];
            });

        return Inertia::render('AbsensiPetugas', [
            'absenHariIni'  => $absenHariIni,
            'summary'       => $summary,
            'riwayat'       => $riwayat,
            'semuaPetugas'  => $semuaPetugas,
            'isKoordinator' => $user->isKoordinator(),
            'geofence'      => $geofence,   // ← BARU
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'status'     => 'required|in:masuk,sakit,izin,dl,lainnya',
            'keterangan' => 'required_if:status,sakit,izin,dl,lainnya|nullable|string|max:500',
            'lat'        => 'nullable|numeric|between:-90,90',
            'lng'        => 'nullable|numeric|between:-180,180',
            'accuracy'   => 'nullable|numeric|min:0',
        ]);

        $user = auth()->user();
        $today = now()->toDateString();

        if (AbsensiPetugas::where('tanggal', $today)->where('nama', $user->name)->exists()) {
            return back()->with('error', 'Anda sudah absen hari ini.');
        }

        // ===== BARU: VALIDASI GEOFENCE (hanya untuk status "masuk") =====
        $pengaturan = Pengaturan::first();
        $jarakMeter = null;
        $latAbsen   = $validated['lat'] ?? null;
        $lngAbsen   = $validated['lng'] ?? null;

        if ($validated['status'] === 'masuk' && $pengaturan && $pengaturan->lat && $pengaturan->lng) {
            // Cek kelengkapan lokasi dari device
            if ($latAbsen === null || $lngAbsen === null) {
                return back()->with('error', 'Lokasi tidak terbaca. Izinkan akses lokasi di browser Anda.');
            }

            // Cek akurasi (tolak kalau GPS terlalu tidak presisi)
            if (($validated['accuracy'] ?? 0) > 500) {
                return back()->with('error', 'Sinyal GPS terlalu lemah. Pindah ke tempat terbuka lalu coba lagi.');
            }

            // Hitung jarak Haversine
            $jarakMeter = (int) round($this->haversine(
                (float) $latAbsen,
                (float) $lngAbsen,
                (float) $pengaturan->lat,
                (float) $pengaturan->lng
            ));

            // Tolak kalau di luar radius
            $radius = (int) ($pengaturan->radius_meter ?? 150);
            if ($jarakMeter > $radius) {
                return back()->with('error', "Absen ditolak. Anda berada {$jarakMeter} m dari sekolah (maksimum {$radius} m).");
            }
        }
        // ===== AKHIR BARU =====

        if ($validated['status'] === 'masuk') {
            $jam = now()->format('H:i:s');
            $status = $jam <= self::BATAS_TEPAT_WAKTU.':00' ? 'tepat_waktu' : 'terlambat';
            $jamMasuk = $jam;
            $keterangan = null;
        } else {
            $status = $validated['status'];
            $jamMasuk = null;
            $keterangan = $validated['keterangan'];
            // Status non-masuk: tidak simpan lokasi (privasi — mereka absen dari rumah/RS)
            $latAbsen = null;
            $lngAbsen = null;
            $jarakMeter = null;
        }

        AbsensiPetugas::create([
            'nama'        => $user->name,
            'jabatan'     => $user->role === 'koordinator' ? 'Koordinator Piket' : 'Guru Piket',
            'tanggal'     => $today,
            'jam_masuk'   => $jamMasuk,
            'status'      => $status,
            'keterangan'  => $keterangan,
            'absen_lat'   => $latAbsen,       // ← BARU
            'absen_lng'   => $lngAbsen,       // ← BARU
            'jarak_meter' => $jarakMeter,     // ← BARU (audit trail)
        ]);

        $pesan = 'Absensi berhasil dicatat.';
        if ($jarakMeter !== null) {
            $pesan .= " (jarak: {$jarakMeter} m dari sekolah)";
        }

        return back()->with('success', $pesan);
    }

    /**
     * Hitung jarak 2 titik di permukaan bumi (meter)
     */
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371000; // radius bumi dalam meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $absen = AbsensiPetugas::findOrFail($id);

        if (!$user->isKoordinator() && $absen->nama !== $user->name) {
            return back()->with('error', 'Tidak punya izin mengubah data ini.');
        }

        $validated = $request->validate([
            'status' => 'required|in:tepat_waktu,terlambat,sakit,izin,dl,lainnya,alpha',
            'jam_masuk' => 'nullable|date_format:H:i',
            'keterangan' => 'nullable|string|max:500',
        ]);

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

        if (!$user->isKoordinator() && $absen->nama !== $user->name) {
            return back()->with('error', 'Tidak punya izin menghapus data ini.');
        }

        $absen->delete();
        return back()->with('success', 'Data absensi dihapus.');
    }
}
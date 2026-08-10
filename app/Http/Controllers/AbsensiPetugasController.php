<?php

namespace App\Http\Controllers;

use App\Models\AbsensiPetugas;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AbsensiPetugasController extends Controller
{
    public function index()
    {
        $hariIni = now()->toDateString();

        return Inertia::render('AbsensiPetugas', [
            'absensiHariIni' => AbsensiPetugas::where('tanggal', $hariIni)
                ->orderBy('jam_masuk')->get(),
            'sudahAbsen' => AbsensiPetugas::where('tanggal', $hariIni)
                ->where('nama', auth()->user()->name)->exists(),
            'userName' => auth()->user()->name,
            'hariIni' => now()->locale('id')->isoFormat('dddd, D MMMM Y'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama'    => 'required|string|max:100',
            'jabatan' => 'nullable|string|max:100',
        ]);

        // Batas tepat waktu: 07:00
        $status = now()->format('H:i') < '07:00' ? 'tepat_waktu' : 'terlambat';

        AbsensiPetugas::firstOrCreate(
            ['tanggal' => now()->toDateString(), 'nama' => $data['nama']],
            [
                'jabatan'   => $data['jabatan'] ?: 'Guru Piket',
                'jam_masuk' => now()->format('H:i:s'),
                'status'    => $status,
            ]
        );

        return back()->with('success', 'Absensi petugas tercatat!');
    }

    public function destroy($id)
    {
        AbsensiPetugas::findOrFail($id)->delete();
        return back()->with('success', 'Absensi dihapus.');
    }
}
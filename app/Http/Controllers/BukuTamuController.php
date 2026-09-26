<?php

namespace App\Http\Controllers;

use App\Models\BukuTamu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BukuTamuController extends Controller
{
    public function index(Request $request)
    {
        $query = BukuTamu::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q
                ->where('nama', 'like', "%{$search}%")
                ->orWhere('instansi', 'like', "%{$search}%")
                ->orWhere('keperluan', 'like', "%{$search}%"));
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal_kunjungan', $request->tanggal);
        }

        $bukuTamu = $query->orderByDesc('tanggal_kunjungan')
            ->orderByDesc('jam_masuk')
            ->paginate(15)
            ->withQueryString();

        $params = $request->only(['search', 'tanggal']);
        if (empty($params)) {
            $params = new \stdClass();
        }

        return Inertia::render('BukuTamu/Index', [
            'bukuTamu' => $bukuTamu,
            'params' => $params,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'telepon' => 'nullable|string|max:20',
            'instansi' => 'nullable|string|max:100',
            'keperluan' => 'required|string|max:500',
            'bertemu_dengan' => 'nullable|string|max:100',
            'jam_masuk' => 'required',
            'tanggal_kunjungan' => 'required|date',
            'catatan' => 'nullable|string|max:500',
            'foto_ktp' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('foto_ktp')) {
            $data['foto_ktp'] = $request->file('foto_ktp')->store('ktp-tamu', 'public');
        }

        BukuTamu::create($data);
        return back()->with('success', 'Data tamu berhasil dicatat.');
    }

    public function update(Request $request, $id)
    {
        $tamu = BukuTamu::findOrFail($id);
        $data = $request->validate([
            'jam_keluar' => 'required',
        ]);
        $tamu->update($data);
        return back()->with('success', 'Jam keluar tamu tercatat.');
    }

    public function destroy($id)
    {
        $tamu = BukuTamu::findOrFail($id);
        if ($tamu->foto_ktp) {
            Storage::disk('public')->delete($tamu->foto_ktp);
        }
        $tamu->delete();
        return back()->with('success', 'Data tamu dihapus.');
    }
}
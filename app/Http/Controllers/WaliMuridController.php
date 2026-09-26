<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\WaliMurid;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WaliMuridController extends Controller
{
    public function index(Request $request)
    {
        $query = WaliMurid::with('siswa');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q
                ->where('nama', 'like', "%{$search}%")
                ->orWhere('telepon', 'like', "%{$search}%")
                ->orWhereHas('siswa', fn ($s) => $s->where('nama', 'like', "%{$search}%")));
        }

        $waliMurid = $query->orderBy('nama')->paginate(10)->withQueryString();

        $daftarSiswa = Siswa::orderBy('kelas')->orderBy('nama')->get(['id', 'nama', 'kelas']);

        $params = $request->only(['search']);
        if (empty($params)) {
            $params = new \stdClass();
        }

        return Inertia::render('WaliMurid/Index', [
            'waliMurid' => $waliMurid,
            'daftarSiswa' => $daftarSiswa,
            'params' => $params,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'siswa_id' => 'required|exists:siswa,id',
            'nama' => 'required|string|max:255',
            'hubungan' => 'required|string|max:50',
            'telepon' => 'required|string|max:20',
            'utama' => 'nullable|boolean',
        ]);

        $data['utama'] = $request->boolean('utama');

        // Jika ditandai utama, nonaktifkan wali lain pada siswa yang sama
        if ($data['utama']) {
            WaliMurid::where('siswa_id', $data['siswa_id'])->update(['utama' => false]);
        }

        WaliMurid::create($data);

        return back()->with('success', 'Data wali murid berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        $wali = WaliMurid::findOrFail($id);

        $data = $request->validate([
            'siswa_id' => 'required|exists:siswa,id',
            'nama' => 'required|string|max:255',
            'hubungan' => 'required|string|max:50',
            'telepon' => 'required|string|max:20',
            'utama' => 'nullable|boolean',
        ]);

        $data['utama'] = $request->boolean('utama');

        if ($data['utama']) {
            WaliMurid::where('siswa_id', $data['siswa_id'])
                ->where('id', '!=', $id)
                ->update(['utama' => false]);
        }

        $wali->update($data);

        return back()->with('success', 'Data wali murid berhasil diperbarui.');
    }

    public function destroy($id)
    {
        WaliMurid::findOrFail($id)->delete();

        return back()->with('success', 'Data wali murid berhasil dihapus.');
    }
}
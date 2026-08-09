<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\WaliKelas;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WaliKelasController extends Controller
{
    public function index(Request $request)
    {
        $query = WaliKelas::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q
                ->where('nama', 'like', "%{$search}%")
                ->orWhere('kelas', 'like', "%{$search}%")
                ->orWhere('telepon', 'like', "%{$search}%"));
        }

        $waliKelas = $query->orderBy('kelas')->paginate(10)->withQueryString();

        // Daftar kelas diambil otomatis dari Data Siswa
        $daftarKelas = Siswa::distinct()->orderBy('kelas')->pluck('kelas')->filter()->values();

        $params = $request->only(['search']);
        if (empty($params)) {
            $params = new \stdClass();
        }

        return Inertia::render('WaliKelas/Index', [
            'waliKelas' => $waliKelas,
            'daftarKelas' => $daftarKelas,
            'params' => $params,
        ]);
    }

    public function store(Request $request)
    {
        // Normalisasi: email kosong menjadi null (boleh dikosongkan)
        $request->merge([
            'email' => trim((string) $request->input('email', '')) !== '' ? $request->input('email') : null,
        ]);

        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'kelas' => 'required|string|max:50',
            'telepon' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'aktif' => 'nullable|boolean',
        ]);

        $data['aktif'] = $request->boolean('aktif', true);

        WaliKelas::create($data);

        return back()->with('success', 'Data wali kelas berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        $wali = WaliKelas::findOrFail($id);

        // Normalisasi: email kosong menjadi null (boleh dikosongkan)
        $request->merge([
            'email' => trim((string) $request->input('email', '')) !== '' ? $request->input('email') : null,
        ]);

        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'kelas' => 'required|string|max:50',
            'telepon' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'aktif' => 'nullable|boolean',
        ]);

        $data['aktif'] = $request->boolean('aktif', true);

        $wali->update($data);

        return back()->with('success', 'Data wali kelas berhasil diperbarui.');
    }

    public function destroy($id)
    {
        WaliKelas::findOrFail($id)->delete();

        return back()->with('success', 'Data wali kelas berhasil dihapus.');
    }

         public function kirimRekap()
    {
        $hasil = app(\App\Services\RekapHarianService::class)->kirimSemua();

        $pesan = "Rekap diproses: {$hasil['dikirim']} terkirim, "
            ."{$hasil['dilewati_sudah']} dilewati (sudah kirim hari ini), "
            ."{$hasil['dilewati_bersih']} dilewati (kelas bersih), "
            ."{$hasil['gagal']} gagal.";

        return back()->with('success', $pesan);
    }
}
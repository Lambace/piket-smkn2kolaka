<?php

namespace App\Http\Controllers;

use App\Models\Pengaturan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PengaturanController extends Controller
{
    public function edit()
    {
        return Inertia::render('Pengaturan/Edit', [
            'pengaturan' => $this->getOrCreatePengaturan(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nama_sekolah'          => 'required|string|max:255',
            'nama_instansi'         => 'nullable|string|max:255',
            'warna_tema'            => 'required|string|max:20',
            'logo'                  => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'logo_instansi'         => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'kop_baris1'            => 'nullable|string|max:255',
            'kop_baris2'            => 'nullable|string|max:255',
            'kop_nama_sekolah'      => 'nullable|string|max:255',
            'alamat'                => 'nullable|string|max:255',
            'telepon'               => 'nullable|string|max:50',
            'email'                 => 'nullable|string|max:100',
            'website'               => 'nullable|string|max:100',
            'server'                => 'nullable|string|max:100',
            // ← BARU: Data Tanda Tangan
            'kepala_sekolah'        => 'nullable|string|max:100',
            'nip_kepala_sekolah'    => 'nullable|string|max:30',
            'koordinator_piket'     => 'nullable|string|max:100',
            'nip_koordinator_piket' => 'nullable|string|max:30',
        ]);

        $pengaturan = \App\Models\Pengaturan::first() ?? new \App\Models\Pengaturan();

        // Simpan semua field teks
        $teksFields = [
            'nama_sekolah', 'nama_instansi', 'warna_tema',
            'kop_baris1', 'kop_baris2', 'kop_nama_sekolah',
            'alamat', 'telepon', 'email', 'website', 'server',
            // ← BARU: Data Tanda Tangan
            'kepala_sekolah', 'nip_kepala_sekolah',
            'koordinator_piket', 'nip_koordinator_piket',
        ];
        foreach ($teksFields as $field) {
            $pengaturan->{$field} = $validated[$field] ?? null;
        }

        // Upload logo sekolah
        if ($request->hasFile('logo')) {
            if ($pengaturan->logo && \Storage::disk('public')->exists($pengaturan->logo)) {
                \Storage::disk('public')->delete($pengaturan->logo);
            }
            $pengaturan->logo = $request->file('logo')->store('logo-sekolah', 'public');
        }

        // Upload logo instansi
        if ($request->hasFile('logo_instansi')) {
            if ($pengaturan->logo_instansi && \Storage::disk('public')->exists($pengaturan->logo_instansi)) {
                \Storage::disk('public')->delete($pengaturan->logo_instansi);
            }
            $pengaturan->logo_instansi = $request->file('logo_instansi')->store('logo-instansi', 'public');
        }

        $pengaturan->save();

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    private function getOrCreatePengaturan(): Pengaturan
    {
        return Pengaturan::first() ?? Pengaturan::create([
            'nama_sekolah' => 'SMKN 2 Kolaka',
            'warna_tema' => '#4f46e5',
        ]);
    }
}
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
        $pengaturan = $this->getOrCreatePengaturan();

        $data = $request->validate([
            'nama_sekolah' => 'required|string|max:100',
            'warna_tema' => [
                'required',
                'string',
                'max:20',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            ],
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:5120',
        ]);

        if ($request->hasFile('logo')) {
            try {
                if ($pengaturan->logo && Storage::disk('public')->exists($pengaturan->logo)) {
                    Storage::disk('public')->delete($pengaturan->logo);
                }
                $data['logo'] = $request->file('logo')->store('logo', 'public');
            } catch (\Throwable $e) {
                Log::error('Gagal upload logo: ' . $e->getMessage());
                return back()->with('error', 'Gagal mengupload logo: ' . $e->getMessage());
            }
        } else {
            unset($data['logo']);
        }

        $pengaturan->update($data);

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
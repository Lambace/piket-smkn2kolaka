<?php

namespace App\Http\Controllers;

use App\Models\Pelanggaran;
use App\Models\Siswa;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PelanggaranController extends Controller
{
    public function index(Request $request)
    {
        $query = Pelanggaran::with(['siswa', 'petugas']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('siswa', fn ($q) => $q
                ->where('nama', 'like', "%{$search}%")
                ->orWhere('nisn', 'like', "%{$search}%")
                ->orWhere('kelas', 'like', "%{$search}%"));
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        // ===== BARU: filter tingkat kelas X / XI / XII =====
        if ($request->filled('tingkat')) {
            $tingkat = $request->tingkat;
            $query->whereHas('siswa', fn ($q) => $q->where('kelas', 'like', "{$tingkat}%"));
        }

        $pelanggaran = $query->orderByDesc('tanggal')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $daftarSiswa = Siswa::with(['waliUtama:id,siswa_id,telepon', 'waliMurid:id,siswa_id,telepon'])
            ->orderBy('kelas')->orderBy('nama')
            ->get(['id', 'nama', 'kelas', 'nisn']);

        $daftarSiswa->each(function ($s) {
            $wali = $s->waliUtama ?? $s->waliMurid->first();
            $s->punya_wa = (bool) ($wali && $wali->telepon);
        });

        // ===== BARU: tingkat ikut di params =====
        $params = $request->only(['search', 'tanggal', 'tingkat']);
        if (empty($params)) {
            $params = new \stdClass();
        }

        return Inertia::render('Pelanggaran/Index', [
            'pelanggaran' => $pelanggaran,
            'daftarSiswa' => $daftarSiswa,
            'params'      => $params,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'siswa_id'          => 'required|exists:siswa,id',
            'tanggal'           => 'required|date',
            'jenis_pelanggaran' => 'required|string|max:100',
            'poin'              => 'required|integer|min:0|max:100',
            'keterangan'        => 'nullable|string|max:500',
            'foto_bukti'        => 'nullable|image|max:2048',
            'kirim_notif'       => 'nullable|boolean',
        ]);

        $kirimNotif = $request->boolean('kirim_notif');
        unset($data['kirim_notif']);

        if ($request->hasFile('foto_bukti')) {
            $data['foto_bukti'] = $request->file('foto_bukti')->store('pelanggaran', 'public');
        }

        $data['petugas_id'] = $request->user()->id;
        $data['status'] = 'dicatat';

        $pelanggaran = Pelanggaran::create($data);

        if (!$kirimNotif) {
            return back()->with('success', 'Data pelanggaran tersimpan tanpa notifikasi.');
        }

        $siswa = $pelanggaran->siswa;
        $wali = $siswa->waliUtama ?? $siswa->waliMurid()->first();

        if ($wali && $wali->telepon) {
            $pesan = "*PEMBERITAHUAN PELANGGARAN*\n"
                . config('app.name') . "\n\n"
                . "Yth. Bapak/Ibu " . $wali->nama . ",\n\n"
                . "Ananda *" . $siswa->nama . "* (" . $siswa->kelas . ") tercatat melakukan pelanggaran:\n"
                . "📅 Tanggal: " . $pelanggaran->tanggal->format('d/m/Y') . "\n"
                . "⚠️ Jenis: " . $pelanggaran->jenis_pelanggaran . "\n"
                . "🎯 Poin: " . $pelanggaran->poin . "\n"
                . "📝 Keterangan: " . ($pelanggaran->keterangan ?? '-') . "\n\n"
                . "Mohon bimbingannya di rumah. Terima kasih.\n"
                . "- Admin Piket";

            app(WhatsAppService::class)->kirim($wali->telepon, $pesan, $wali);
            return back()->with('success', 'Data tersimpan & notifikasi WA terkirim ke orang tua.');
        }

        return back()->with('success', 'Data tersimpan. Siswa ini belum punya nomor WA orang tua — notifikasi dilewati.');
    }

    public function update(Request $request, $id)
    {
        $pelanggaran = Pelanggaran::findOrFail($id);
        $data = $request->validate([
            'status' => 'required|in:dicatat,diproses,selesai',
        ]);
        $pelanggaran->update($data);
        return back()->with('success', 'Status pelanggaran diperbarui.');
    }

    public function destroy($id)
    {
        $pelanggaran = Pelanggaran::findOrFail($id);
        if ($pelanggaran->foto_bukti) {
            Storage::disk('public')->delete($pelanggaran->foto_bukti);
        }
        $pelanggaran->delete();
        return back()->with('success', 'Data pelanggaran dihapus.');
    }
}
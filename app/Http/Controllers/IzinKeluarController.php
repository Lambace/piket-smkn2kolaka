<?php

namespace App\Http\Controllers;

use App\Models\IzinKeluar;
use App\Models\Siswa;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IzinKeluarController extends Controller
{
    public function index(Request $request)
    {
        $query = IzinKeluar::with(['siswa', 'disetujuiOleh']);

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

        $izinKeluar = $query->orderByDesc('tanggal')
            ->orderByDesc('jam_keluar')
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

        return Inertia::render('IzinKeluar/Index', [
            'izinKeluar'  => $izinKeluar,
            'daftarSiswa' => $daftarSiswa,
            'params'      => $params,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'siswa_id'    => 'required|exists:siswa,id',
            'tanggal'     => 'required|date',
            'jam_keluar'  => 'required',
            'jenis'       => 'required|string|max:50',
            'keterangan'  => 'nullable|string|max:500',
            'kirim_notif' => 'nullable|boolean',
        ]);

        $kirimNotif = $request->boolean('kirim_notif');
        unset($data['kirim_notif']);

        $data['status'] = 'menunggu';

        $izin = IzinKeluar::create($data);

        if (!$kirimNotif) {
            return back()->with('success', 'Data izin keluar tersimpan tanpa notifikasi.');
        }

        $siswa = $izin->siswa;
        $wali = $siswa->waliUtama;
        if (!$wali) {
            $wali = $siswa->waliMurid()->first();
        }

        if ($wali && $wali->telepon) {
            $pesan = "*PEMBERITAHUAN IZIN KELUAR*\n"
                . config('app.name') . "\n\n"
                . "Yth. Bapak/Ibu " . $wali->nama . ",\n\n"
                . "Ananda *" . $siswa->nama . "* (" . $siswa->kelas . ") tercatat meminta izin keluar sekolah:\n"
                . "📅 Tanggal: " . $izin->tanggal->format('d/m/Y') . "\n"
                . "⏰ Jam keluar: " . $izin->jam_keluar . "\n"
                . "📋 Jenis: " . $izin->jenis . "\n"
                . "📝 Keterangan: " . ($izin->keterangan ?? '-') . "\n"
                . "🕓 Status: " . $izin->status . "\n\n"
                . "Mohon diketahui. Terima kasih.\n"
                . "- Admin Piket";

            app(WhatsAppService::class)->kirim($wali->telepon, $pesan, $wali);

            return back()->with('success', 'Data tersimpan & notifikasi WA terkirim ke orang tua.');
        }

        return back()->with('success', 'Data tersimpan. Siswa ini belum punya nomor WA orang tua — notifikasi dilewati.');
    }

    public function update(Request $request, $id)
    {
        $izin = IzinKeluar::findOrFail($id);

        $data = $request->validate([
            'status' => 'required|in:menunggu,disetujui,ditolak,kembali',
        ]);

        if ($data['status'] === 'disetujui' && $izin->status !== 'disetujui') {
            $data['disetujui_oleh'] = $request->user()->id;
            $data['disetujui_pada'] = now();
        }

        if ($data['status'] === 'kembali' && !$izin->jam_kembali) {
            $data['jam_kembali'] = now()->format('H:i');
        }

        $izin->update($data);

        return back()->with('success', 'Status izin keluar diperbarui.');
    }

    public function destroy($id)
    {
        IzinKeluar::findOrFail($id)->delete();

        return back()->with('success', 'Data izin keluar dihapus.');
    }
}
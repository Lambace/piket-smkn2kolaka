<?php

namespace App\Http\Controllers;

use App\Models\Keterlambatan;
use App\Models\Siswa;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KeterlambatanController extends Controller
{
    public function index(Request $request)
    {
        $query = Keterlambatan::with(['siswa', 'petugas']);

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

        $keterlambatan = $query->orderByDesc('tanggal')
            ->orderByDesc('jam_datang')
            ->paginate(15)
            ->withQueryString();

        $daftarSiswa = Siswa::with(['waliUtama:id,siswa_id,telepon', 'waliMurid:id,siswa_id,telepon'])
            ->orderBy('kelas')->orderBy('nama')
            ->get(['id', 'nama', 'kelas', 'nisn']);

        // Tandai siswa yang punya nomor WA orang tua
        $daftarSiswa->each(function ($s) {
            $wali = $s->waliUtama ?? $s->waliMurid->first();
            $s->punya_wa = (bool) ($wali && $wali->telepon);
        });

        // params selalu jadi object, bukan array kosong
        $params = $request->only(['search', 'tanggal']);
        if (empty($params)) {
            $params = new \stdClass();
        }

        return Inertia::render('Keterlambatan/Index', [
            'keterlambatan' => $keterlambatan,
            'daftarSiswa' => $daftarSiswa,
            'params' => $params,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'siswa_id' => 'required|exists:siswa,id',
            'tanggal' => 'required|date',
            'jam_datang' => 'required',
            'menit_terlambat' => 'required|integer|min:0',
            'keterangan' => 'nullable|string|max:500',
            'kirim_notif' => 'nullable|boolean',
        ]);

        $kirimNotif = $request->boolean('kirim_notif');
        unset($data['kirim_notif']);

        $data['petugas_id'] = $request->user()->id;
        $data['status'] = 'dicatat';

        $keterlambatan = Keterlambatan::create($data);

        // ===== KONDISI 1: tombol "Simpan" → hanya simpan =====
        if (!$kirimNotif) {
            return back()->with('success', 'Data keterlambatan tersimpan tanpa notifikasi.');
        }

        // ===== KONDISI 2: tombol "Simpan & Kirim WA ke Wali" =====
        $siswa = $keterlambatan->siswa;

        $wali = $siswa->waliUtama;
        if (!$wali) {
            $wali = $siswa->waliMurid()->first();
        }

        // Kondisi 2a: ada nomor orang tua → simpan + kirim
        if ($wali && $wali->telepon) {
            $pesan = "*PEMBERITAHUAN KETERLAMBATAN*\n"
                . config('app.name') . "\n\n"
                . "Yth. Bapak/Ibu " . $wali->nama . ",\n\n"
                . "Ananda *" . $siswa->nama . "* (" . $siswa->kelas . ") tercatat terlambat:\n"
                . "📅 Tanggal: " . $keterlambatan->tanggal->format('d/m/Y') . "\n"
                . "⏰ Jam datang: " . $keterlambatan->jam_datang . "\n"
                . "⏱️ Terlambat: " . $keterlambatan->menit_terlambat . " menit\n"
                . "📝 Keterangan: " . ($keterlambatan->keterangan ?? '-') . "\n\n"
                . "Mohon perhatiannya. Terima kasih.\n"
                . "- Admin Piket";

            app(WhatsAppService::class)->kirim($wali->telepon, $pesan, $wali);

            return back()->with('success', 'Data tersimpan & notifikasi WA terkirim ke orang tua.');
        }

        // Kondisi 2b: TIDAK ada nomor orang tua → cukup simpan
        return back()->with('success', 'Data tersimpan. Siswa ini belum punya nomor WA orang tua — notifikasi dilewati.');
    }

    public function update(Request $request, $id)
    {
        $keterlambatan = Keterlambatan::findOrFail($id);

        $data = $request->validate([
            'status' => 'required|in:dicatat,dimaafkan,dihukum',
        ]);

        $keterlambatan->update($data);

        return back()->with('success', 'Status keterlambatan diperbarui.');
    }

    public function destroy($id)
    {
        Keterlambatan::findOrFail($id)->delete();

        return back()->with('success', 'Data keterlambatan dihapus.');
    }
}
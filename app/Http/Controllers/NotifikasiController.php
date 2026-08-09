<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        $query = Notifikasi::query()->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $notifikasi = $query->paginate(15)->withQueryString();

        $params = $request->only(['status']);
        if (empty($params)) {
            $params = new \stdClass();
        }

        return Inertia::render('Notifikasi/Index', [
            'notifikasi' => $notifikasi,
            'params' => $params,
        ]);
    }

    public function retry($id)
    {
        $notifikasi = Notifikasi::findOrFail($id);

        $baru = app(WhatsAppService::class)->kirim(
            $notifikasi->nomor_tujuan,
            $notifikasi->pesan,
            $notifikasi->penerima
        );

        return back()->with(
            $baru->status === 'terkirim' ? 'success' : 'error',
            $baru->status === 'terkirim'
                ? 'Notifikasi berhasil dikirim ulang.'
                : 'Gagal mengirim ulang: '.$baru->pesan_error
        );
    }
}
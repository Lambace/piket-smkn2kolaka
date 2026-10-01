<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use App\Models\User;
use App\Services\FonnteService;
use App\Services\PengirimWaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MonitoringController extends Controller
{
    public function index(FonnteService $fonnte, PengirimWaResolver $resolver)
    {
        $now = Carbon::now('Asia/Makassar');
        $hariIni = $now->isoFormat('dddd');
        $todayStr = $now->toDateString();

        // ===== KOORDINATOR HARI INI =====
        $koordinatorHariIni = $resolver->resolve($hariIni);

        // ===== RINGKASAN HARI INI =====
        $ringkasanHariIni = [
            'banner' => Notifikasi::whereDate('created_at', $todayStr)
                ->where('jenis', 'whatsapp')
                ->where('nomor_tujuan', 'like', '%@g.us')
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status='terkirim' THEN 1 ELSE 0 END) as sukses, SUM(CASE WHEN status='gagal' THEN 1 ELSE 0 END) as gagal")
                ->first(),
            'rekap_wali' => Notifikasi::whereDate('created_at', $todayStr)
                ->where('jenis', 'rekap')
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status='terkirim' THEN 1 ELSE 0 END) as sukses, SUM(CASE WHEN status='gagal' THEN 1 ELSE 0 END) as gagal")
                ->first(),
        ];

        // ===== RIWAYAT 7 HARI TERAKHIR =====
        $riwayat = [];
        for ($i = 6; $i >= 0; $i--) {
            $tgl = $now->copy()->subDays($i);
            $tglStr = $tgl->toDateString();
            $hari = $tgl->isoFormat('dddd');

            $banner = Notifikasi::whereDate('created_at', $tglStr)
                ->where('jenis', 'whatsapp')
                ->where('nomor_tujuan', 'like', '%@g.us')
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status='terkirim' THEN 1 ELSE 0 END) as sukses")
                ->first();

            $rekap = Notifikasi::whereDate('created_at', $tglStr)
                ->where('jenis', 'rekap')
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status='terkirim' THEN 1 ELSE 0 END) as sukses")
                ->first();

            $riwayat[] = [
                'tanggal' => $tglStr,
                'hari' => $hari,
                'banner_total' => (int) ($banner->total ?? 0),
                'banner_sukses' => (int) ($banner->sukses ?? 0),
                'rekap_total' => (int) ($rekap->total ?? 0),
                'rekap_sukses' => (int) ($rekap->sukses ?? 0),
            ];
        }

        // ===== STATUS DEVICE PER KOORDINATOR =====
        $statusDevice = [];
        $koordinatorList = User::whereIn('role', ['koordinator', 'petugas'])
            ->whereNotNull('no_wa')
            ->whereNotNull('fonnte_token')
            ->orderBy('hari_piket')
            ->get();

        foreach ($koordinatorList as $k) {
            $status = $fonnte->cekStatusDevice($k->fonnte_token);
            $statusDevice[] = [
                'id' => $k->id,
                'nama' => $k->name,
                'hari_piket' => $k->hari_piket,
                'no_wa' => $k->no_wa,
                'role' => $k->role,
                'device_status' => $status['status'],
                'device_name' => $status['device'],
            ];
        }

        // ===== PENGATURAN GRUP =====
        $pengaturan = \App\Models\Pengaturan::first();

        return Inertia::render('Monitoring/Index', [
            'hari_ini' => $hariIni,
            'tanggal_sekarang' => $now->isoFormat('dddd, D MMMM Y'),
            'koordinator_hari_ini' => $koordinatorHariIni ? [
                'nama' => $koordinatorHariIni->name,
                'hari_piket' => $koordinatorHariIni->hari_piket,
            ] : null,
            'ringkasan_hari_ini' => [
                'banner' => [
                    'total' => (int) ($ringkasanHariIni['banner']->total ?? 0),
                    'sukses' => (int) ($ringkasanHariIni['banner']->sukses ?? 0),
                    'gagal' => (int) ($ringkasanHariIni['banner']->gagal ?? 0),
                ],
                'rekap_wali' => [
                    'total' => (int) ($ringkasanHariIni['rekap_wali']->total ?? 0),
                    'sukses' => (int) ($ringkasanHariIni['rekap_wali']->sukses ?? 0),
                    'gagal' => (int) ($ringkasanHariIni['rekap_wali']->gagal ?? 0),
                ],
            ],
            'riwayat' => $riwayat,
            'status_device' => $statusDevice,
            'grup_sekolah' => $pengaturan?->wa_grup ?: env('WA_GROUP_ID', '-'),
        ]);
    }
}
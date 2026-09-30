<?php

namespace App\Services;

use App\Models\AbsensiPetugas;
use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManagerStatic as Image;

class BannerPiketService
{
    /**
     * Render banner piket on-demand.
     * Dipanggil dari route /banner/piket.png?hari=Rabu&tanggal=2026-09-30
     * Return binary PNG (bukan file tersimpan).
     */
    public function render(string $hari, string $tanggalStr): string
    {
        $tanggal    = Carbon::parse($tanggalStr, 'Asia/Makassar');
        $pengaturan = Pengaturan::first();

        // ===== HITUNG DATA REAL =====
        $petugasJadwal = User::whereIn('role', ['petugas', 'koordinator'])
            ->where('hari_piket', $hari)
            ->get();

        $absensi = AbsensiPetugas::whereDate('tanggal', $tanggalStr)->get();

        $hadir = 0;
        $alpha = 0;
        foreach ($petugasJadwal as $u) {
            $key = strtolower(trim($u->name));
            $r   = $absensi->first(fn($a) => strtolower(trim($a->nama)) === $key);
            if ($r && in_array($r->status, ['tepat_waktu', 'terlambat'])) {
                $hadir++;
            } else {
                $alpha++;
            }
        }

        // ===== GENERATE GAMBAR =====
        $templatePath = public_path('images/banner-bg.png');
        if (!File::exists($templatePath)) {
            abort(500, 'Template banner-bg.png tidak ditemukan di public/images/');
        }

        $image = Image::make($templatePath);

        // A. Overlay Logo Dinamis
        if ($pengaturan?->logo) {
            $logoPath = public_path('storage/' . $pengaturan->logo);
            if (File::exists($logoPath)) {
                $logo = Image::make($logoPath)->resize(180, 180, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $image->insert($logo, 'top', 0, 50);
            }
        }

        // B. Overlay Tanggal
        $image->text($tanggal->isoFormat('dddd, D MMMM Y'), 540, 750, function($font) {
            $font->size(40);
            $font->color('#2c3e50');
            $font->align('center');
            $font->valign('middle');
        });

        // C. Overlay Angka Hadir
        $image->text((string)$hadir, 340, 1300, function($font) {
            $font->size(100);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });
        $image->text('HADIR', 340, 1200, function($font) {
            $font->size(22);
            $font->color('#ffffff');
            $font->align('center');
        });

        // D. Overlay Angka Alpha
        $image->text((string)$alpha, 740, 1300, function($font) {
            $font->size(100);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });
        $image->text('ALPHA', 740, 1200, function($font) {
            $font->size(22);
            $font->color('#ffffff');
            $font->align('center');
        });

        return (string) $image->encode('png');
    }
}
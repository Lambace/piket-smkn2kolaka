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
    public function render(string $hari, string $tanggalStr): string
    {
        $tanggal    = Carbon::parse($tanggalStr, 'Asia/Makassar');
        $pengaturan = Pengaturan::first();

        $petugasJadwal = User::whereIn('role', ['petugas', 'koordinator'])
            ->where('hari_piket', $hari)->get();
        $absensi = AbsensiPetugas::whereDate('tanggal', $tanggalStr)->get();

        $hadir = 0; $alpha = 0;
        foreach ($petugasJadwal as $u) {
            $key = strtolower(trim($u->name));
            $r = $absensi->first(fn($a) => strtolower(trim($a->nama)) === $key);
            if ($r && in_array($r->status, ['tepat_waktu', 'terlambat'])) { $hadir++; } else { $alpha++; }
        }

        $templatePath = public_path('images/banner-bg.png');
        if (!File::exists($templatePath)) abort(404, 'Template banner tidak ditemukan');

        $image = Image::make($templatePath);

        if ($pengaturan?->logo) {
            $logoPath = public_path('storage/' . $pengaturan->logo);
            if (File::exists($logoPath)) {
                $logo = Image::make($logoPath)->resize(180, 180, fn($c) => $c->aspectRatio());
                $image->insert($logo, 'top', 0, 50);
            }
        }

        $image->text($tanggal->isoFormat('dddd, D MMMM Y'), 540, 750, function($f) {
            $f->size(40); $f->color('#2c3e50'); $f->align('center'); $f->valign('middle');
        });
        $image->text((string)$hadir, 340, 1300, function($f) {
            $f->size(100); $f->color('#ffffff'); $f->align('center'); $f->valign('middle');
        });
        $image->text('HADIR', 340, 1200, function($f) {
            $f->size(22); $f->color('#ffffff'); $f->align('center');
        });
        $image->text((string)$alpha, 740, 1300, function($f) {
            $f->size(100); $f->color('#ffffff'); $f->align('center'); $f->valign('middle');
        });
        $image->text('ALPHA', 740, 1200, function($f) {
            $f->size(22); $f->color('#ffffff'); $f->align('center');
        });

        return (string) $image->encode('png');
    }
}
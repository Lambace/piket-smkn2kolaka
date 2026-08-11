<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Piket</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 10pt; color: #222; margin: 0; padding: 12mm; }
    .kop { border-bottom: 4px double #333; padding-bottom: 8px; margin-bottom: 15px; display: table; width: 100%; }
    .kop-logo { display: table-cell; width: 75px; vertical-align: middle; text-align: center; }
    .kop-logo img { max-width: 65px; max-height: 65px; }
    .kop-teks { display: table-cell; vertical-align: middle; text-align: center; }
    .kop-nama { font-size: 16pt; font-weight: bold; text-transform: uppercase; margin: 0; }
    .kop-sub { font-size: 10pt; font-weight: bold; margin: 0; }
    .kop-alamat { font-size: 8.5pt; margin: 2px 0; }
    .judul { text-align: center; margin: 10px 0 15px; }
    .judul h2 { margin: 0; font-size: 13pt; text-transform: uppercase; }
    .judul .periode { font-style: italic; margin-top: 4px; }
    h3.judul-tabel { font-size: 11pt; margin: 18px 0 6px; padding: 4px 8px; background: #eef2ff; border-left: 4px solid #4f46e5; page-break-after: avoid; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 9pt; page-break-inside: avoid; }
    th, td { border: 1px solid #888; padding: 4px 6px; vertical-align: top; }
    th { background: #e5e7eb; text-align: center; }
    .tengah { text-align: center; }
    tr.kosong td { text-align: center; font-style: italic; color: #888; padding: 12px; }
    .ttd { margin-top: 35px; text-align: right; }
    .ttd .spasi { height: 60px; }
    .ttd .nama { font-weight: bold; text-decoration: underline; }
    .catatan { margin-top: 25px; font-size: 8.5pt; color: #666; text-align: center; font-style: italic; }
</style>
</head>
<body>

@include('laporan.partials.kop')

<div class="judul">
    <h2>Laporan Piket {{ $pengaturan->nama_sekolah ?? 'SMKN 2 KOLAKA' }}</h2>
    <div class="periode">{{ $labelPeriode }}</div>
</div>

@include('laporan.partials.absensi-petugas')
@include('laporan.partials.ringkasan')
@include('laporan.partials.terlambat')
@include('laporan.partials.izin-keluar')
@include('laporan.partials.pelanggaran')
@include('laporan.partials.tamu')
@include('laporan.partials.terlambat-per-kelas')
@include('laporan.partials.terlambat-per-jurusan')
@include('laporan.partials.trend-pelanggaran')
@include('laporan.partials.status-pelanggaran')
@include('laporan.partials.jenis-pelanggaran')
@include('laporan.partials.aktivitas-terbaru')
@include('laporan.partials.siswa-poin-tertinggi')
@include('laporan.partials.siswa-paling-terlambat')

<div class="ttd">
    <div>Kolaka, {{ now()->locale('id')->isoFormat('D MMMM Y') }}</div>
    <div>Petugas Piket</div>
    <div class="spasi"></div>
    <div class="nama">{{ $dicetakOleh }}</div>
</div>
<div class="catatan">Dicetak otomatis pada {{ $waktuCetak }} — Sistem Informasi Piket</div>

</body>
</html>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Piket</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 9.5pt; color: #1e293b; padding: 12mm 12mm 16mm; }

    /* ===== Baris meta atas ===== */
    .meta-top { display: table; width: 100%; margin-bottom: 10px; }
    .meta-top .kiri { display: table-cell; width: 40%; font-size: 7.5pt; color: #64748b; }
    .meta-top .tengah { display: table-cell; width: 60%; text-align: center; font-size: 7.5pt; color: #334155; text-transform: uppercase; letter-spacing: 1px; }

    /* ===== Kop ===== */
    .kop { display: table; width: 100%; border-bottom: 3px double #334155; padding-bottom: 10px; margin-bottom: 12px; }
    .kop-logo { display: table-cell; width: 78px; vertical-align: middle; }
    .kop-logo img { width: 60px; height: 60px; }
    .kop-teks { display: table-cell; vertical-align: middle; }
    .kop-pemerintah { font-size: 8.5pt; color: #475569; letter-spacing: 1px; }
    .kop-nama { font-size: 15pt; font-weight: bold; color: #0f172a; letter-spacing: 0.5px; margin: 2px 0; }
    .kop-alamat { font-size: 7.5pt; color: #64748b; }

    /* ===== Judul ===== */
    .judul { text-align: center; margin: 12px 0 10px; }
    .judul h2 { font-size: 12pt; text-transform: uppercase; letter-spacing: 1px; color: #0f172a; }

    /* ===== Info bar 3 kolom ===== */
    .info-bar { display: table; width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; background: #f8fafc; margin-bottom: 12px; }
    .info-bar .cell { display: table-cell; padding: 7px 10px; font-size: 7.5pt; color: #475569; }
    .info-bar .cell + .cell { border-left: 1px solid #e2e8f0; }
    .info-bar strong { color: #0f172a; }

    /* ===== Judul tabel ===== */
    h3.judul-tabel { font-size: 9.5pt; text-transform: uppercase; letter-spacing: 0.5px; color: #0f172a; margin: 16px 0 6px; padding: 5px 10px; background: #eef2ff; border-left: 4px solid #4f46e5; page-break-after: avoid; }

    /* ===== Tabel ===== */
    table { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-bottom: 8px; page-break-inside: avoid; }
    thead th { background: #f1f5f9; color: #475569; text-transform: uppercase; font-size: 7pt; letter-spacing: 0.8px; padding: 6px 8px; border: 1px solid #cbd5e1; text-align: center; }
    tbody td { padding: 6px 8px; border: 1px solid #e2e8f0; vertical-align: top; }
    tbody tr:nth-child(even) td { background: #f8fafc; }
    .tengah { text-align: center; }
    .tebal { font-weight: bold; }
    .sub { display: block; font-size: 7pt; color: #94a3b8; margin-top: 1px; }
    tr.kosong td { text-align: center; font-style: italic; color: #94a3b8; padding: 14px; }

    /* ===== Pill status ===== */
    .pill { display: inline-block; padding: 2px 9px; border-radius: 10px; font-size: 6.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
    .pill-hijau  { background: #d1fae5; color: #059669; }
    .pill-kuning { background: #fef3c7; color: #d97706; }
    .pill-merah  { background: #fee2e2; color: #dc2626; }
    .pill-biru   { background: #dbeafe; color: #2563eb; }
    .pill-ungu   { background: #ede9fe; color: #7c3aed; }

    /* ===== Tanda tangan ===== */
    .ttd { margin-top: 30px; text-align: right; font-size: 9pt; }
    .ttd .spasi { height: 55px; }
    .ttd .nama { font-weight: bold; text-decoration: underline; }
</style>
</head>
<body>

{{-- Baris meta atas (seperti gambar) --}}
<div class="meta-top">
    <div class="kiri">{{ now()->format('d/m/y, H.i') }}</div>
    <div class="tengah">Laporan Piket {{ $pengaturan->nama_sekolah ?? 'SMKN 2 KOLAKA' }}</div>
</div>

@include('laporan.partials.kop')

<div class="judul">
    <h2>Laporan Piket {{ $pengaturan->nama_sekolah ?? 'SMKN 2 KOLAKA' }}</h2>
</div>

{{-- Info bar 3 kolom --}}
<div class="info-bar">
    <div class="cell"><strong>PERIODE REKAP:</strong> {{ $labelPeriode }}</div>
    <div class="cell"><strong>TANGGAL CETAK:</strong> {{ now()->locale('id')->isoFormat('D MMMM Y') }}</div>
    <div class="cell"><strong>TOTAL DATA:</strong> {{ $totalData }} Catatan</div>
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

{{-- Footer: nama aplikasi kiri + nomor halaman kanan (otomatis tiap halaman) --}}
<script type="text/php">
if (isset($pdf)) {
    $font = $fontMetrics->get_font("Helvetica", "normal");
    $pdf->page_text(34, $pdf->get_height() - 24, "Sistem Informasi Piket - {{ $pengaturan->nama_sekolah ?? 'SMKN 2 KOLAKA' }}", $font, 7, array(0.58, 0.64, 0.72));
    $pdf->page_text($pdf->get_width() - 110, $pdf->get_height() - 24, "Halaman " . $pdf->get_page_number() . " dari " . $pdf->get_page_count(), $font, 7, array(0.58, 0.64, 0.72));
}
</script>

</body>
</html>
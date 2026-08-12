<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar Hadir Piket</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #000; padding: 10mm 12mm; }

    table.kop-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
    table.kop-table td { border: none; padding: 0; vertical-align: middle; }
    table.kop-table td.kop-logo { width: 95px; text-align: center; }
    table.kop-table td.kop-logo img { width: 75px; height: 75px; }
    table.kop-table td.kop-teks { text-align: center; padding: 0 8px; }
    .kop-baris1, .kop-baris2 { font-size: 11pt; font-weight: bold; }
    .kop-nama { font-size: 13pt; font-weight: bold; margin: 2px 0; }
    .kop-alamat { font-size: 8.5pt; margin: 1px 0; }
    .kop-link { color: #1a0dab; }
    .kop-garis { margin: 6px 0 14px 0; border-top: 2.5px solid #000; border-bottom: 2.5px solid #000; height: 4px; }

    .judul-blok { text-align: center; margin-bottom: 6px; }
    .judul-blok .j1 { font-size: 11pt; font-weight: bold; }
    .judul-blok .j2 { font-size: 11pt; font-weight: bold; }
    .judul-blok .j3 { font-size: 10pt; }

    .tanggal-baris { display: table; width: 100%; margin: 10px 0; }
    .tanggal-baris .lbl { display: table-cell; width: 170px; }
    .tanggal-baris .val { display: table-cell; }

    table.hadir { width: 100%; border-collapse: collapse; font-size: 9pt; }
    table.hadir th, table.hadir td { border: 1px solid #000; padding: 5px 6px; vertical-align: top; }
    table.hadir th { text-align: center; font-weight: bold; }
    .tengah { text-align: center; }
    .cek { font-size: 12pt; font-weight: bold; }

    table.ttd { width: 100%; margin-top: 24px; border-collapse: collapse; }
    table.ttd td { width: 50%; text-align: center; vertical-align: top; font-size: 10pt; border: none; padding: 0; }
    .ttd-tanggal { height: 16px; margin-bottom: 2px; }
    .ttd-space { height: 60px; }
    .ttd-nama { font-weight: bold; text-decoration: underline; }
    .ttd-nip { font-size: 9.5pt; margin-top: 3px; }
</style>
</head>
<body>

<table class="kop-table">
    <tr>
        <td class="kop-logo">
            @if($logoInstansi ?? null)<img src="{{ $logoInstansi }}" alt="Logo Instansi">@endif
        </td>
        <td class="kop-teks">
            <div class="kop-baris1">{{ $pengaturan->kop_baris1 ?? 'PEMERINTAH PROVINSI SULAWESI TENGGARA' }}</div>
            <div class="kop-baris2">{{ $pengaturan->kop_baris2 ?? 'DINAS PENDIDIKAN DAN KEBUDAYAAN' }}</div>
            <div class="kop-nama">{{ strtoupper($pengaturan->kop_nama_sekolah ?: ($pengaturan->nama_sekolah ?? 'SEKOLAH MENENGAH KEJURUAN (SMK) NEGERI 2 KOLAKA')) }}</div>
            <div class="kop-alamat">{{ $pengaturan->alamat ?? 'Jln. Poros Kolaka - Pomalaa KM. 16 Kec. Baula Kab. Kolaka Provinsi SULTRA' }}</div>
            <div class="kop-alamat">
                E-mail <span class="kop-link">{{ $pengaturan->email ?? 'smknsatubaula@yahoo.co.id' }}</span>
                &nbsp;&nbsp;HP. {{ $pengaturan->telepon ?? '082346999111' }}
            </div>
        </td>
        <td class="kop-logo">
            @if($logo ?? null)<img src="{{ $logo }}" alt="Logo Sekolah">@endif
        </td>
    </tr>
</table>
<div class="kop-garis"></div>

<div class="judul-blok">
    <div class="j1">DAFTAR HADIR PIKET</div>
    <div class="j2">PENDIDIK DAN TENAGA KEPENDIDIKAN {{ strtoupper($pengaturan->nama_sekolah ?? 'SMKN 2 KOLAKA') }}</div>
    <div class="j3">Kecamatan Baula, Kabupaten Kolaka, Provinsi Sulawesi Tenggara</div>
</div>

<div class="tanggal-baris">
    <div class="lbl">Hari/Tanggal/Bulan</div>
    <div class="val">: {{ $hariTanggal }}</div>
</div>

<table class="hadir">
    <thead>
        <tr>
            <th rowspan="2" style="width:30px">No</th>
            <th rowspan="2">Nama</th>
            <th rowspan="2" style="width:26px">JK</th>
            <th rowspan="2" style="width:110px">NIP</th>
            <th rowspan="2" style="width:36px">Gol</th>
            <th rowspan="2" style="width:110px">Status Kepegawaian</th>
            <th colspan="5">Status Kehadiran</th>
            <th rowspan="2" style="width:46px">Ket.</th>
        </tr>
        <tr>
            <th style="width:22px">H</th>
            <th style="width:22px">A</th>
            <th style="width:22px">I</th>
            <th style="width:22px">S</th>
            <th style="width:22px">DL</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $i => $r)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td><strong>{{ $r['nama'] }}</strong></td>
            <td class="tengah">{{ $r['jk'] }}</td>
            <td>{{ $r['nip'] }}</td>
            <td class="tengah">{{ $r['gol'] }}</td>
            <td>{{ $r['status'] }}</td>
            <td class="tengah cek">{{ $r['h'] > 0 ? '✓' : '' }}</td>
            <td class="tengah cek">{{ $r['a'] > 0 ? '✓' : '' }}</td>
            <td class="tengah cek">{{ $r['i'] ? '✓' : '' }}</td>
            <td class="tengah cek">{{ $r['s'] ? '✓' : '' }}</td>
            <td class="tengah cek">{{ $r['dl'] ? '✓' : '' }}</td>
            <td>{{ $r['ket'] }}</td>
        </tr>
        @endforeach
        @for($i = 0; $i < 2; $i++)
        <tr>
            <td class="tengah">&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
            <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
            <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
        </tr>
        @endfor
    </tbody>
</table>

<table class="ttd">
    <tr>
        <td>
            <div class="ttd-tanggal">&nbsp;</div>
            Kepala Sekolah
            <div class="ttd-space"></div>
            <span class="ttd-nama">{{ $pengaturan->kepala_sekolah ?? '……………………………………' }}</span>
            <div class="ttd-nip">NIP. {{ $pengaturan->nip_kepala_sekolah ?? '………………………………' }}</div>
        </td>
        <td>
            <div class="ttd-tanggal">{{ $tempatTanggal }}</div>
            Koordinator Piket
            <div class="ttd-space"></div>
            <span class="ttd-nama">{{ $koordinator?->name ?? '……………………………………' }}</span>
            <div class="ttd-nip">NIP. {{ $koordinator?->nip ?? '………………………………' }}</div>
        </td>
    </tr>
</table>

</body>
</html>
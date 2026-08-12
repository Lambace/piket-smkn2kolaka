<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Piket</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #000; padding: 10mm 12mm; }

    /* Kop */
    table.kop-table { width: 100%; border-collapse: collapse; }
    table.kop-table td { border: none; padding: 0; vertical-align: middle; }
    table.kop-table td.kop-logo { width: 95px; text-align: center; }
    table.kop-table td.kop-logo img { width: 70px; height: 70px; }
    table.kop-table td.kop-teks { text-align: center; padding: 0 8px; }
    .kop-baris1, .kop-baris2 { font-size: 11pt; font-weight: bold; }
    .kop-nama { font-size: 13pt; font-weight: bold; margin: 2px 0; }
    .kop-alamat { font-size: 8.5pt; margin: 1px 0; }
    .kop-garis { margin: 6px 0 12px 0; border-top: 2.5px solid #000; border-bottom: 2.5px solid #000; height: 4px; }

    .judul-laporan { text-align: center; font-size: 12pt; font-weight: bold; text-transform: uppercase; }
    .periode-laporan { text-align: center; font-size: 9.5pt; margin: 2px 0 12px 0; }

    h3.seksi { font-size: 10pt; font-weight: bold; margin: 12px 0 5px 0; }

    table.data { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-bottom: 4px; }
    table.data th, table.data td { border: 1px solid #000; padding: 4px 6px; vertical-align: top; }
    table.data th { text-align: center; font-weight: bold; background-color: #eeeeee; }
    .tengah { text-align: center; }

    table.ringkas { width: 100%; border-collapse: collapse; font-size: 9pt; }
    table.ringkas td { border: 1px solid #000; padding: 4px 8px; }
    table.ringkas td.lbl { font-weight: bold; width: 45%; background-color: #eeeeee; }

    .footer-cetak { margin-top: 10px; font-size: 8pt; color: #444; text-align: right; }

    /* Tanda tangan */
    table.ttd { width: 100%; margin-top: 24px; border-collapse: collapse; }
    table.ttd td { width: 50%; text-align: center; vertical-align: top; font-size: 10pt; border: none; padding: 0; }
    .ttd-tanggal { height: 16px; margin-bottom: 2px; }
    .ttd-space { height: 60px; }
    .ttd-nama { font-weight: bold; text-decoration: underline; }
    .ttd-nip { font-size: 9.5pt; margin-top: 3px; }
</style>
</head>
<body>

{{-- ===== KOP ===== --}}
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
            <div class="kop-alamat">E-mail {{ $pengaturan->email ?? 'smknsatubaula@yahoo.co.id' }} &nbsp; HP. {{ $pengaturan->telepon ?? '082346999111' }}</div>
        </td>
        <td class="kop-logo">
            @if($logo ?? null)<img src="{{ $logo }}" alt="Logo Sekolah">@endif
        </td>
    </tr>
</table>
<div class="kop-garis"></div>

<div class="judul-laporan">Laporan Piket</div>
<div class="periode-laporan">{{ $labelPeriode }}</div>

{{-- ===== A. REKAP PETUGAS PIKET (SEMUA NAMA + STATUS) ===== --}}
<h3 class="seksi">A. Absensi Petugas Piket</h3>
<table class="data">
    <thead>
        <tr>
            <th style="width:24px">No</th>
            <th>Nama</th>
            <th style="width:105px">Jabatan</th>
            <th style="width:45px">Jam</th>
            <th style="width:110px">Status</th>
            <th style="width:140px">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @php
            $labelStatus = [
                'tepat_waktu' => 'Hadir Tepat Waktu',
                'terlambat'   => 'Terlambat',
                'sakit'       => 'Sakit',
                'izin'        => 'Izin',
                'dl'          => 'Dinas Luar',
                'alpha'       => 'Alpha',
            ];
        @endphp
        @forelse($rekapPetugas as $i => $r)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td><strong>{{ $r['nama'] }}</strong></td>
            <td>{{ $r['jabatan'] }}</td>
            <td class="tengah">{{ $r['jam'] }}</td>
            <td class="tengah"><strong>{{ $labelStatus[$r['status']] ?? ucfirst($r['status']) }}</strong></td>
            <td>{{ $r['keterangan'] ?: '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="tengah">Tidak ada data petugas.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- ===== B. RINGKASAN ===== --}}
<h3 class="seksi">B. Ringkasan</h3>
<table class="ringkas">
    @foreach($ringkasan as $r)
    <tr>
        <td class="lbl">{{ $r['label'] }}</td>
        <td>{{ $r['nilai'] }}</td>
    </tr>
    @endforeach
</table>

{{-- ===== C. KETERLAMBATAN SISWA ===== --}}
<h3 class="seksi">C. Keterlambatan Siswa ({{ $keterlambatan->count() }} kejadian)</h3>
<table class="data">
    <thead>
        <tr>
            <th style="width:24px">No</th>
            <th>Tanggal</th>
            <th>Nama</th>
            <th style="width:60px">Kelas</th>
            <th style="width:55px">Menit</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($keterlambatan as $i => $k)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td>{{ $k->tanggal?->isoFormat('D MMM Y') }}</td>
            <td>{{ $k->siswa?->nama ?? '-' }}</td>
            <td class="tengah">{{ $k->siswa?->kelas ?? '-' }}</td>
            <td class="tengah">{{ $k->menit_terlambat }}</td>
            <td>{{ $k->keterangan ?? '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="tengah">Tidak ada keterlambatan.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- ===== D. IZIN KELUAR ===== --}}
<h3 class="seksi">D. Izin Keluar ({{ $izinKeluar->count() }} kejadian)</h3>
<table class="data">
    <thead>
        <tr>
            <th style="width:24px">No</th>
            <th>Tanggal</th>
            <th>Nama</th>
            <th style="width:60px">Kelas</th>
            <th style="width:80px">Jenis</th>
            <th style="width:70px">Kembali</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($izinKeluar as $i => $k)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td>{{ $k->tanggal?->isoFormat('D MMM Y') }}</td>
            <td>{{ $k->siswa?->nama ?? '-' }}</td>
            <td class="tengah">{{ $k->siswa?->kelas ?? '-' }}</td>
            <td>{{ $k->jenis }}</td>
            <td class="tengah">{{ $k->jam_kembali ?? '-' }}</td>
            <td>{{ $k->keterangan ?? '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="7" class="tengah">Tidak ada izin keluar.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- ===== E. PELANGGARAN ===== --}}
<h3 class="seksi">E. Pelanggaran ({{ $pelanggaran->count() }} kejadian / {{ $pelanggaran->sum('poin') }} poin)</h3>
<table class="data">
    <thead>
        <tr>
            <th style="width:24px">No</th>
            <th>Tanggal</th>
            <th>Nama</th>
            <th style="width:60px">Kelas</th>
            <th>Jenis Pelanggaran</th>
            <th style="width:40px">Poin</th>
            <th style="width:70px">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($pelanggaran as $i => $k)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td>{{ $k->tanggal?->isoFormat('D MMM Y') }}</td>
            <td>{{ $k->siswa?->nama ?? '-' }}</td>
            <td class="tengah">{{ $k->siswa?->kelas ?? '-' }}</td>
            <td>{{ $k->jenis_pelanggaran }}</td>
            <td class="tengah">{{ $k->poin }}</td>
            <td class="tengah">{{ $k->status }}</td>
        </tr>
        @empty
        <tr><td colspan="7" class="tengah">Tidak ada pelanggaran.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- ===== F. BUKU TAMU ===== --}}
<h3 class="seksi">F. Kunjungan Tamu ({{ $tamu->count() }} kunjungan)</h3>
<table class="data">
    <thead>
        <tr>
            <th style="width:24px">No</th>
            <th>Tanggal</th>
            <th>Nama Tamu</th>
            <th style="width:90px">Instansi</th>
            <th>Bertemu Dengan</th>
            <th>Keperluan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($tamu as $i => $k)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td>{{ $k->tanggal_kunjungan?->isoFormat('D MMM Y') }}</td>
            <td>{{ $k->nama }}</td>
            <td>{{ $k->instansi ?? '-' }}</td>
            <td>{{ $k->bertemu_dengan ?? '-' }}</td>
            <td>{{ $k->keperluan }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="tengah">Tidak ada kunjungan tamu.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- ===== G. STATISTIK ===== --}}
<h3 class="seksi">G. Statistik Keterlambatan</h3>
<table class="data">
    <tr>
        <th style="width:50%">Per Kelas</th>
        <th style="width:50%">Per Jurusan</th>
    </tr>
    <tr>
        <td style="border:1px solid #000; padding:4px 8px;">
            @forelse($perKelas as $p) {{ $p->label }}: <strong>{{ $p->jumlah }}</strong> @if(!$loop->last) • @endif @empty - @endforelse
        </td>
        <td style="border:1px solid #000; padding:4px 8px;">
            @forelse($perJurusan as $p) {{ $p->label }}: <strong>{{ $p->jumlah }}</strong> @if(!$loop->last) • @endif @empty - @endforelse
        </td>
    </tr>
</table>

<h3 class="seksi">H. Poin Pelanggaran Tertinggi</h3>
<table class="data">
    <thead>
        <tr>
            <th style="width:24px">No</th>
            <th>Nama</th>
            <th style="width:60px">Kelas</th>
            <th style="width:70px">Kasus</th>
            <th style="width:70px">Total Poin</th>
        </tr>
    </thead>
    <tbody>
        @forelse($topPoin as $i => $k)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td>{{ $k->siswa?->nama ?? '-' }}</td>
            <td class="tengah">{{ $k->siswa?->kelas ?? '-' }}</td>
            <td class="tengah">{{ $k->jumlah_kasus }}</td>
            <td class="tengah"><strong>{{ $k->total_poin }}</strong></td>
        </tr>
        @empty
        <tr><td colspan="5" class="tengah">Tidak ada data.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer-cetak">
    Dicetak oleh: {{ $dicetakOleh }} — {{ $waktuCetak }}
</div>

{{-- ===== BLOK TANDA TANGAN ===== --}}
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
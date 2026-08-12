<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Piket</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #1a202c; padding: 10mm 12mm; }

    /* ===== Meta atas ===== */
    table.meta { width: 100%; font-size: 7.5pt; color: #718096; margin-bottom: 8px; }
    table.meta td { border: none; padding: 0; }
    table.meta td.right { text-align: right; text-transform: uppercase; letter-spacing: 1.5px; font-weight: bold; color: #4a5568; }

    /* ===== Kop ===== */
    table.kop-table { width: 100%; border-collapse: collapse; }
    table.kop-table td { border: none; padding: 0; vertical-align: middle; }
    table.kop-table td.kop-logo { width: 95px; text-align: center; }
    table.kop-table td.kop-logo img { width: 70px; height: 70px; }
    table.kop-table td.kop-teks { text-align: center; padding: 0 8px; }
    .kop-baris1, .kop-baris2 { font-size: 11pt; font-weight: bold; }
    .kop-nama { font-size: 13pt; font-weight: bold; margin: 2px 0; }
    .kop-alamat { font-size: 8.5pt; margin: 1px 0; }
    .kop-link { color: #2b6cb0; }
    .kop-garis { margin: 6px 0 12px 0; border-top: 2.5px solid #000; border-bottom: 2.5px solid #000; height: 4px; }

    .judul { text-align: center; font-size: 12pt; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; margin: 8px 0 10px 0; }

    /* ===== Info bar ===== */
    table.infobar { width: 100%; border-collapse: collapse; font-size: 8pt; margin-bottom: 6px; }
    table.infobar td { border: 1px solid #cbd5e0; background: #f7fafc; padding: 6px 8px; }
    table.infobar b { color: #2d3748; }

    /* ===== Header seksi ===== */
    .seksi { margin: 14px 0 6px 0; padding: 5px 10px; background: #e8ecfb; border-left: 4px solid #5b6ee1; font-weight: bold; font-size: 9.5pt; text-transform: uppercase; color: #2d3748; }

    /* ===== Tabel data ===== */
    table.data { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
    table.data th { background: #dfe8f7; color: #2d3748; text-transform: uppercase; font-size: 7.5pt; letter-spacing: 0.5px; padding: 6px; border: 1px solid #cbd5e0; text-align: center; }
    table.data td { border: 1px solid #e2e8f0; padding: 6px; vertical-align: top; }
    table.data tr.alt td { background: #f7fafc; }
    .tengah { text-align: center; }
    .sub { display: block; font-size: 7pt; color: #a0aec0; }

    /* ===== Badge status ===== */
    .badge { display: inline-block; padding: 2px 9px; border-radius: 9px; font-size: 7pt; font-weight: bold; letter-spacing: 0.5px; }
    .b-hadir    { background: #e6f6e6; color: #2f855a; }
    .b-terlambat{ background: #fdf3d7; color: #b7791f; }
    .b-sakit    { background: #f3e8ff; color: #6b46c1; }
    .b-izin     { background: #e0f2fe; color: #0369a1; }
    .b-dl       { background: #e0e7ff; color: #4338ca; }
    .b-alpha    { background: #fee2e2; color: #b91c1c; }
    .b-mnt      { background: #f6ad55; color: #7b341e; }
    .b-poin     { background: #feb2b2; color: #9b2c2c; }

    /* ===== Tanda tangan ===== */
    table.ttd { width: 100%; margin-top: 26px; border-collapse: collapse; }
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
            <div class="kop-nama">{{ strtoupper($pengaturan->kop_nama_sekolah ?: ($pengaturan->nama_sekolah ?? 'SMKN 2 KOLAKA')) }}</div>
            <div class="kop-alamat">{{ $pengaturan->alamat ?? 'Jln. Poros Kolaka - Pomalaa KM. 16 Kec. Baula Kab. Kolaka Provinsi SULTRA' }}</div>
            <div class="kop-alamat">
                E-mail <span class="kop-link">{{ $pengaturan->email ?? 'smknsatubaula@yahoo.co.id' }}</span>
                &nbsp; Telp: {{ $pengaturan->telepon ?? '082346999111' }}
            </div>
            @if(($pengaturan->website ?? null) || ($pengaturan->server ?? null))
            <div class="kop-alamat">
                Website: <span class="kop-link">{{ $pengaturan->website ?? '-' }}</span>
                &nbsp; Server: <span class="kop-link">{{ $pengaturan->server ?? '-' }}</span>
            </div>
            @endif
        </td>
        <td class="kop-logo">
            @if($logo ?? null)<img src="{{ $logo }}" alt="Logo Sekolah">@endif
        </td>
    </tr>
</table>
<div class="kop-garis"></div>

<div class="judul">Laporan Piket Tim Piket {{ strtoupper(now()->isoFormat('dddd')) }}</div>

{{-- ===== INFO BAR ===== --}}
<table class="infobar">
    <tr>
        <td style="width:40%"><b>PERIODE REKAP:</b> {{ $labelPeriode }}</td>
        <td style="width:30%"><b>TANGGAL CETAK:</b> {{ now()->isoFormat('D MMMM Y') }}</td>
        <td style="width:30%"><b>TOTAL DATA:</b> {{ $totalData }} Catatan</td>
    </tr>
</table>

{{-- ===== TABEL 1: REKAP ABSENSI PETUGAS ===== --}}
<div class="seksi">Tabel 1 — Rekap Absensi Petugas Piket</div>
<table class="data">
    <thead>
        <tr>
            <th style="width:28px">No</th>
            <th>Nama Petugas / Jabatan</th>
            <th style="width:80px">Tanggal</th>
            <th style="width:75px">Waktu</th>
            <th style="width:110px">Status</th>
        </tr>
    </thead>
    <tbody>
        @php
            $badgeStatus = [
                'tepat_waktu' => ['cls' => 'b-hadir',     'label' => 'TEPAT WAKTU'],
                'terlambat'   => ['cls' => 'b-terlambat', 'label' => 'TERLAMBAT'],
                'sakit'       => ['cls' => 'b-sakit',     'label' => 'SAKIT'],
                'izin'        => ['cls' => 'b-izin',      'label' => 'IZIN'],
                'dl'          => ['cls' => 'b-dl',        'label' => 'DINAS LUAR'],
                'alpha'       => ['cls' => 'b-alpha',     'label' => 'ALPHA'],
            ];
        @endphp
        @forelse($rekapPetugas as $i => $r)
        <tr class="{{ $loop->odd ? '' : 'alt' }}">
            <td class="tengah">{{ $i+1 }}</td>
            <td>
                <strong>{{ $r['nama'] }}</strong>
                <span class="sub">{{ $r['jabatan'] }}@if($r['keterangan']) — “{{ $r['keterangan'] }}” @endif</span>
            </td>
            <td class="tengah">{{ $r['tanggal'] }}</td>
            <td class="tengah"><strong>{{ $r['jam'] !== '-' ? $r['jam'].' WITA' : '-' }}</strong></td>
            <td class="tengah">
                <span class="badge {{ $badgeStatus[$r['status']]['cls'] ?? 'b-alpha' }}">
                    {{ $badgeStatus[$r['status']]['label'] ?? strtoupper($r['status']) }}
                </span>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" class="tengah">Tidak ada data petugas.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- ===== TABEL 2: RINGKASAN ===== --}}
<div class="seksi">Tabel 2 — Ringkasan Piket</div>
<table class="data">
    <thead>
        <tr>
            <th style="width:50%">Indikator</th>
            <th style="width:50%">Nilai</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ringkasan as $r)
        <tr class="{{ $loop->odd ? '' : 'alt' }}">
            <td><strong>{{ $r['label'] }}</strong></td>
            <td>{{ $r['nilai'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ===== TABEL 3: KETERLAMBATAN ===== --}}
<div class="seksi">Tabel 3 — Data Keterlambatan Siswa ({{ $keterlambatan->count() }})</div>
<table class="data">
    <thead>
        <tr>
            <th style="width:28px">No</th>
            <th style="width:80px">Tanggal</th>
            <th>Nama Siswa</th>
            <th style="width:60px">Kelas</th>
            <th style="width:60px">Menit</th>
            <th style="width:140px">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($keterlambatan as $i => $k)
        <tr class="{{ $loop->odd ? '' : 'alt' }}">
            <td class="tengah">{{ $i+1 }}</td>
            <td class="tengah">{{ $k->tanggal?->isoFormat('D MMM Y') }}</td>
            <td>
                <strong>{{ $k->siswa?->nama ?? '-' }}</strong>
                <span class="sub">NISN: {{ $k->siswa?->nisn ?? '-' }}</span>
            </td>
            <td class="tengah">{{ $k->siswa?->kelas ?? '-' }}</td>
            <td class="tengah"><span class="badge b-mnt">{{ $k->menit_terlambat }} MNT</span></td>
            <td>{{ $k->keterangan ?? '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="tengah">Tidak ada keterlambatan.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- ===== TABEL 4: IZIN KELUAR ===== --}}
<div class="seksi">Tabel 4 — Data Izin Keluar ({{ $izinKeluar->count() }})</div>
<table class="data">
    <thead>
        <tr>
            <th style="width:28px">No</th>
            <th style="width:80px">Tanggal</th>
            <th>Nama Siswa</th>
            <th style="width:60px">Kelas</th>
            <th style="width:80px">Jenis</th>
            <th style="width:70px">Kembali</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($izinKeluar as $i => $k)
        <tr class="{{ $loop->odd ? '' : 'alt' }}">
            <td class="tengah">{{ $i+1 }}</td>
            <td class="tengah">{{ $k->tanggal?->isoFormat('D MMM Y') }}</td>
            <td>
                <strong>{{ $k->siswa?->nama ?? '-' }}</strong>
                <span class="sub">NISN: {{ $k->siswa?->nisn ?? '-' }}</span>
            </td>
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

{{-- ===== TABEL 5: PELANGGARAN ===== --}}
<div class="seksi">Tabel 5 — Data Pelanggaran ({{ $pelanggaran->count() }} / {{ $pelanggaran->sum('poin') }} Poin)</div>
<table class="data">
    <thead>
        <tr>
            <th style="width:28px">No</th>
            <th style="width:80px">Tanggal</th>
            <th>Nama Siswa</th>
            <th style="width:60px">Kelas</th>
            <th>Jenis Pelanggaran</th>
            <th style="width:50px">Poin</th>
            <th style="width:80px">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($pelanggaran as $i => $k)
        <tr class="{{ $loop->odd ? '' : 'alt' }}">
            <td class="tengah">{{ $i+1 }}</td>
            <td class="tengah">{{ $k->tanggal?->isoFormat('D MMM Y') }}</td>
            <td>
                <strong>{{ $k->siswa?->nama ?? '-' }}</strong>
                <span class="sub">NISN: {{ $k->siswa?->nisn ?? '-' }}</span>
            </td>
            <td class="tengah">{{ $k->siswa?->kelas ?? '-' }}</td>
            <td>{{ $k->jenis_pelanggaran }}</td>
            <td class="tengah"><span class="badge b-poin">{{ $k->poin }}</span></td>
            <td class="tengah">{{ $k->status }}</td>
        </tr>
        @empty
        <tr><td colspan="7" class="tengah">Tidak ada pelanggaran.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- ===== TABEL 6: BUKU TAMU ===== --}}
<div class="seksi">Tabel 6 — Kunjungan Tamu ({{ $tamu->count() }})</div>
<table class="data">
    <thead>
        <tr>
            <th style="width:28px">No</th>
            <th style="width:80px">Tanggal</th>
            <th>Nama Tamu</th>
            <th style="width:90px">Instansi</th>
            <th style="width:110px">Bertemu Dengan</th>
            <th>Keperluan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($tamu as $i => $k)
        <tr class="{{ $loop->odd ? '' : 'alt' }}">
            <td class="tengah">{{ $i+1 }}</td>
            <td class="tengah">{{ $k->tanggal_kunjungan?->isoFormat('D MMM Y') }}</td>
            <td><strong>{{ $k->nama }}</strong></td>
            <td>{{ $k->instansi ?? '-' }}</td>
            <td>{{ $k->bertemu_dengan ?? '-' }}</td>
            <td>{{ $k->keperluan }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="tengah">Tidak ada kunjungan tamu.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- ===== TABEL 7: STATISTIK ===== --}}
<div class="seksi">Tabel 7 — Statistik Keterlambatan &amp; Poin Tertinggi</div>
<table class="data">
    <tr>
        <th style="width:50%">Per Kelas</th>
        <th style="width:50%">Per Jurusan</th>
    </tr>
    <tr>
        <td style="border:1px solid #e2e8f0; padding:6px;">
            @forelse($perKelas as $p){{ $p->label }}: <strong>{{ $p->jumlah }}</strong>@if(!$loop->last) &nbsp;•&nbsp; @endif @empty - @endforelse
        </td>
        <td style="border:1px solid #e2e8f0; padding:6px;">
            @forelse($perJurusan as $p){{ $p->label }}: <strong>{{ $p->jumlah }}</strong>@if(!$loop->last) &nbsp;•&nbsp; @endif @empty - @endforelse
        </td>
    </tr>
    <tr>
        <th colspan="2" style="margin-top:4px;">Poin Pelanggaran Tertinggi</th>
    </tr>
    <tr>
        <td colspan="2" style="border:1px solid #e2e8f0; padding:6px;">
            @forelse($topPoin as $p)
                {{ $p->siswa?->nama ?? '-' }} ({{ $p->siswa?->kelas ?? '-' }}): <span class="badge b-poin">{{ $p->total_poin }} POIN</span>@if(!$loop->last) &nbsp;•&nbsp; @endif
            @empty - @endforelse
        </td>
    </tr>
</table>

{{-- ===== BLOK TANDA TANGAN (prioritas data Pengaturan) ===== --}}
<table class="ttd">
    <tr>
        {{-- KIRI: KEPALA SEKOLAH (dari Pengaturan) --}}
        <td>
            <div class="ttd-tanggal">&nbsp;</div>
            Kepala Sekolah
            <div class="ttd-space"></div>
            <span class="ttd-nama">
                {{ $pengaturan->kepala_sekolah ?? '……………………………………' }}
            </span>
            <div class="ttd-nip">
                NIP. {{ $pengaturan->nip_kepala_sekolah ?? '………………………………' }}
            </div>
        </td>

        {{-- KANAN: KOORDINATOR PIKET (prioritas Pengaturan, fallback akun) --}}
        <td>
            <div class="ttd-tanggal">{{ $tempatTanggal }}</div>
            Koordinator Piket
            <div class="ttd-space"></div>
            <span class="ttd-nama">
                {{ $pengaturan->koordinator_piket ?: ($koordinator?->name ?? '……………………………………') }}
            </span>
            <div class="ttd-nip">
                NIP. {{ $pengaturan->nip_koordinator_piket ?: ($koordinator?->nip ?? '………………………………') }}
            </div>
        </td>
    </tr>
</table>

</body>
</html>
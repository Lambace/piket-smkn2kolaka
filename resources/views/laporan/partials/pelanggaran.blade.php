<h3 class="judul-tabel">Tabel 5 — Data Pelanggaran ({{ $pelanggaran->count() }})</h3>
<table>
    <thead>
        <tr>
            <th style="width:28px">No</th>
            <th style="width:70px">Tanggal</th>
            <th>Nama Siswa</th>
            <th style="width:48px">Kelas</th>
            <th>Jenis Pelanggaran</th>
            <th style="width:40px">Poin</th>
            <th style="width:70px">Status</th>
        </tr>
    </thead>
    <tbody>
    @forelse($pelanggaran as $i => $p)
        @php
            $pill = match(strtolower((string) $p->status)) {
                'selesai', 'terbina' => 'pill-hijau',
                'diproses'          => 'pill-kuning',
                default             => 'pill-biru',
            };
        @endphp
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td class="tengah">{{ \Carbon\Carbon::parse($p->tanggal)->isoFormat('D MMM Y') }}</td>
            <td>
                <span class="tebal">{{ $p->siswa->nama ?? '-' }}</span>
                <span class="sub">NISN: {{ $p->siswa->nisn ?? '-' }}</span>
            </td>
            <td class="tengah">{{ $p->siswa->kelas ?? '-' }}</td>
            <td>{{ $p->jenis_pelanggaran ?? '-' }}</td>
            <td class="tengah"><span class="tebal" style="color:#dc2626">{{ $p->poin ?? 0 }}</span></td>
            <td class="tengah"><span class="pill {{ $pill }}">{{ ucfirst($p->status ?? '-') }}</span></td>
        </tr>
    @empty
        <tr class="kosong"><td colspan="7">Tidak ada data pelanggaran</td></tr>
    @endforelse
    </tbody>
</table>
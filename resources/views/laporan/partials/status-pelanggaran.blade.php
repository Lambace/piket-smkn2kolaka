<h3 class="judul-tabel">Tabel 10 — Status Pelanggaran</h3>
<table>
    <thead>
        <tr><th style="width:28px">No</th><th>Status</th><th style="width:100px">Jumlah</th></tr>
    </thead>
    <tbody>
    @forelse($statusPelanggaran as $i => $d)
        @php
            $pill = match(strtolower((string) $d->label)) {
                'selesai', 'terbina' => 'pill-hijau',
                'diproses'          => 'pill-kuning',
                default             => 'pill-biru',
            };
        @endphp
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td><span class="pill {{ $pill }}">{{ ucfirst($d->label) }}</span></td>
            <td class="tengah"><span class="tebal">{{ $d->jumlah }}</span></td>
        </tr>
    @empty
        <tr class="kosong"><td colspan="3">Tidak ada data</td></tr>
    @endforelse
    </tbody>
</table>
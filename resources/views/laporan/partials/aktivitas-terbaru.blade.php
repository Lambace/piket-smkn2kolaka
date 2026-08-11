<h3 class="judul-tabel">Tabel 12 — Aktivitas Terbaru</h3>
<table>
    <thead>
        <tr>
            <th style="width:28px">No</th>
            <th style="width:100px">Waktu</th>
            <th style="width:85px">Tipe</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
    @forelse($aktivitas as $i => $a)
        @php
            $pill = match($a['tipe']) {
                'Terlambat'   => 'pill-merah',
                'Izin Keluar' => 'pill-kuning',
                'Pelanggaran' => 'pill-ungu',
                default       => 'pill-biru',
            };
        @endphp
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td>{{ \Carbon\Carbon::parse($a['waktu'])->isoFormat('D MMM, HH.mm') }}</td>
            <td class="tengah"><span class="pill {{ $pill }}">{{ $a['tipe'] }}</span></td>
            <td>{{ $a['teks'] }}</td>
        </tr>
    @empty
        <tr class="kosong"><td colspan="4">Tidak ada aktivitas</td></tr>
    @endforelse
    </tbody>
</table>
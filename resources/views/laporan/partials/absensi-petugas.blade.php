<h3 class="judul-tabel">Tabel 1 — Rekap Absensi Petugas Piket</h3>
<table>
    <thead>
        <tr>
            <th style="width:30px">No</th>
            <th>Nama Petugas / Jabatan</th>
            <th style="width:70px">Tanggal</th>
            <th style="width:65px">Waktu</th>
            <th style="width:85px">Status</th>
        </tr>
    </thead>
    <tbody>
    @forelse($absensiPetugas as $i => $a)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td>
                <span class="tebal">{{ $a->nama }}</span>
                <span class="sub">{{ $a->jabatan }}</span>
            </td>
            <td class="tengah">{{ \Carbon\Carbon::parse($a->tanggal)->isoFormat('D MMM Y') }}</td>
            <td class="tengah tebal">{{ $a->jam_masuk ? substr($a->jam_masuk,0,5).' WITA' : '-' }}</td>
            <td class="tengah">
                @if($a->status === 'tepat_waktu')
                    <span class="pill pill-hijau">Hadir</span>
                @else
                    <span class="pill pill-kuning">Terlambat</span>
                @endif
            </td>
        </tr>
    @empty
        <tr class="kosong"><td colspan="5">Tidak ada absensi petugas pada periode ini</td></tr>
    @endforelse
    </tbody>
</table>
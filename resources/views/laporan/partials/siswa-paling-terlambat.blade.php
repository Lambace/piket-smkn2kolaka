<h3 class="judul-tabel">Tabel 14 — Siswa Paling Sering Terlambat</h3>
<table>
    <thead>
        <tr>
            <th style="width:28px">No</th>
            <th>Nama Siswa</th>
            <th style="width:48px">Kelas</th>
            <th style="width:90px">Jumlah Terlambat</th>
            <th style="width:90px">Rata-rata Menit</th>
        </tr>
    </thead>
    <tbody>
    @forelse($topTerlambat as $i => $k)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td>
                <span class="tebal">{{ $k->siswa->nama ?? '-' }}</span>
                <span class="sub">NISN: {{ $k->siswa->nisn ?? '-' }}</span>
            </td>
            <td class="tengah">{{ $k->siswa->kelas ?? '-' }}</td>
            <td class="tengah"><span class="pill pill-kuning">{{ $k->jumlah }} kali</span></td>
            <td class="tengah"><span class="tebal">{{ round($k->rata_menit, 1) }}</span></td>
        </tr>
    @empty
        <tr class="kosong"><td colspan="5">Tidak ada data</td></tr>
    @endforelse
    </tbody>
</table>
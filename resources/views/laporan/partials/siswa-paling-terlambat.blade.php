<h3 class="judul-tabel">Tabel 14 — Siswa Paling Sering Terlambat</h3>
<table>
    <thead><tr><th style="width:28px">No</th><th style="width:85px">NISN</th><th>Nama Siswa</th><th style="width:50px">Kelas</th><th style="width:80px">Jumlah Terlambat</th><th style="width:80px">Rata-rata Menit</th></tr></thead>
    <tbody>
    @forelse($topTerlambat as $i => $k)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td>{{ $k->siswa->nisn ?? '-' }}</td>
            <td>{{ $k->siswa->nama ?? '-' }}</td>
            <td class="tengah">{{ $k->siswa->kelas ?? '-' }}</td>
            <td class="tengah">{{ $k->jumlah }}</td>
            <td class="tengah">{{ round($k->rata_menit, 1) }}</td>
        </tr>
    @empty
        <tr class="kosong"><td colspan="6">Tidak ada data</td></tr>
    @endforelse
    </tbody>
</table>
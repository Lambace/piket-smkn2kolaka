<h3 class="judul-tabel">Tabel 3 — Data Keterlambatan Siswa ({{ $keterlambatan->count() }})</h3>
<table>
    <thead><tr><th style="width:28px">No</th><th style="width:75px">Tanggal</th><th style="width:85px">NISN</th><th>Nama Siswa</th><th style="width:50px">Kelas</th><th style="width:45px">Menit</th><th>Keterangan</th></tr></thead>
    <tbody>
    @forelse($keterlambatan as $i => $k)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td class="tengah">{{ \Carbon\Carbon::parse($k->tanggal)->isoFormat('D MMM Y') }}</td>
            <td>{{ $k->siswa->nisn ?? '-' }}</td>
            <td>{{ $k->siswa->nama ?? '-' }}</td>
            <td class="tengah">{{ $k->siswa->kelas ?? '-' }}</td>
            <td class="tengah">{{ $k->menit_terlambat }}</td>
            <td>{{ $k->keterangan ?? '-' }}</td>
        </tr>
    @empty
        <tr class="kosong"><td colspan="7">Tidak ada data keterlambatan</td></tr>
    @endforelse
    </tbody>
</table>
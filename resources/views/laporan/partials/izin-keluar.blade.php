<h3 class="judul-tabel">Tabel 4 — Data Izin Keluar ({{ $izinKeluar->count() }})</h3>
<table>
    <thead><tr><th style="width:28px">No</th><th style="width:75px">Tanggal</th><th style="width:85px">NISN</th><th>Nama Siswa</th><th style="width:50px">Kelas</th><th style="width:60px">Jenis</th><th style="width:60px">Jam Keluar</th><th>Keterangan</th></tr></thead>
    <tbody>
    @forelse($izinKeluar as $i => $iz)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td class="tengah">{{ \Carbon\Carbon::parse($iz->tanggal)->isoFormat('D MMM Y') }}</td>
            <td>{{ $iz->siswa->nisn ?? '-' }}</td>
            <td>{{ $iz->siswa->nama ?? '-' }}</td>
            <td class="tengah">{{ $iz->siswa->kelas ?? '-' }}</td>
            <td class="tengah">{{ $iz->jenis ?? '-' }}</td>
            <td class="tengah">{{ $iz->jam_keluar ?? '-' }}</td>
            <td>{{ $iz->keterangan ?? '-' }}</td>
        </tr>
    @empty
        <tr class="kosong"><td colspan="8">Tidak ada data izin keluar</td></tr>
    @endforelse
    </tbody>
</table>
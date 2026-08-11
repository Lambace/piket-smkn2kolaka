<h3 class="judul-tabel">Tabel 1 — Rekap Absensi Petugas Piket</h3>
<table>
    <thead><tr><th style="width:28px">No</th><th style="width:75px">Tanggal</th><th>Nama</th><th>Jabatan</th><th style="width:65px">Jam Masuk</th><th style="width:85px">Status</th></tr></thead>
    <tbody>
    @forelse($absensiPetugas as $i => $a)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td class="tengah">{{ \Carbon\Carbon::parse($a->tanggal)->isoFormat('D MMM Y') }}</td>
            <td>{{ $a->nama }}</td>
            <td>{{ $a->jabatan }}</td>
            <td class="tengah">{{ $a->jam_masuk ? substr($a->jam_masuk,0,5) : '-' }}</td>
            <td class="tengah">{{ $a->status === 'tepat_waktu' ? 'Tepat Waktu' : 'Terlambat' }}</td>
        </tr>
    @empty
        <tr class="kosong"><td colspan="6">Tidak ada absensi petugas pada periode ini</td></tr>
    @endforelse
    </tbody>
</table>
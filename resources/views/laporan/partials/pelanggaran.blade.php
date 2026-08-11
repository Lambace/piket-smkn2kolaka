<h3 class="judul-tabel">Tabel 5 — Data Pelanggaran ({{ $pelanggaran->count() }})</h3>
<table>
    <thead><tr><th style="width:28px">No</th><th style="width:75px">Tanggal</th><th style="width:85px">NISN</th><th>Nama Siswa</th><th style="width:50px">Kelas</th><th>Jenis Pelanggaran</th><th style="width:40px">Poin</th><th style="width:60px">Status</th></tr></thead>
    <tbody>
    @forelse($pelanggaran as $i => $p)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td class="tengah">{{ \Carbon\Carbon::parse($p->tanggal)->isoFormat('D MMM Y') }}</td>
            <td>{{ $p->siswa->nisn ?? '-' }}</td>
            <td>{{ $p->siswa->nama ?? '-' }}</td>
            <td class="tengah">{{ $p->siswa->kelas ?? '-' }}</td>
            <td>{{ $p->jenis_pelanggaran ?? '-' }}</td>
            <td class="tengah">{{ $p->poin ?? 0 }}</td>
            <td class="tengah">{{ ucfirst($p->status ?? '-') }}</td>
        </tr>
    @empty
        <tr class="kosong"><td colspan="8">Tidak ada data pelanggaran</td></tr>
    @endforelse
    </tbody>
</table>
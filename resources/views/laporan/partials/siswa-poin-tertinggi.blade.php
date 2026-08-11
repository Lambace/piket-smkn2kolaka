<h3 class="judul-tabel">Tabel 13 — Siswa Poin Tertinggi</h3>
<table>
    <thead><tr><th style="width:28px">No</th><th style="width:85px">NISN</th><th>Nama Siswa</th><th style="width:50px">Kelas</th><th style="width:70px">Jumlah Kasus</th><th style="width:60px">Total Poin</th></tr></thead>
    <tbody>
    @forelse($topPoin as $i => $p)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td>{{ $p->siswa->nisn ?? '-' }}</td>
            <td>{{ $p->siswa->nama ?? '-' }}</td>
            <td class="tengah">{{ $p->siswa->kelas ?? '-' }}</td>
            <td class="tengah">{{ $p->jumlah_kasus }}</td>
            <td class="tengah"><strong>{{ $p->total_poin }}</strong></td>
        </tr>
    @empty
        <tr class="kosong"><td colspan="6">Tidak ada data</td></tr>
    @endforelse
    </tbody>
</table>
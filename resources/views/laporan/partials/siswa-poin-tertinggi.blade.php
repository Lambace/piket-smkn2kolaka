<h3 class="judul-tabel">Tabel 13 — Siswa Poin Tertinggi</h3>
<table>
    <thead>
        <tr>
            <th style="width:28px">No</th>
            <th>Nama Siswa</th>
            <th style="width:48px">Kelas</th>
            <th style="width:75px">Jumlah Kasus</th>
            <th style="width:70px">Total Poin</th>
        </tr>
    </thead>
    <tbody>
    @forelse($topPoin as $i => $p)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td>
                <span class="tebal">{{ $p->siswa->nama ?? '-' }}</span>
                <span class="sub">NISN: {{ $p->siswa->nisn ?? '-' }}</span>
            </td>
            <td class="tengah">{{ $p->siswa->kelas ?? '-' }}</td>
            <td class="tengah">{{ $p->jumlah_kasus }}</td>
            <td class="tengah"><span class="pill pill-merah">{{ $p->total_poin }} poin</span></td>
        </tr>
    @empty
        <tr class="kosong"><td colspan="5">Tidak ada data</td></tr>
    @endforelse
    </tbody>
</table>
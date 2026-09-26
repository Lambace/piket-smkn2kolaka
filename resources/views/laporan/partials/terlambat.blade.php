<h3 class="judul-tabel">Tabel 3 — Data Keterlambatan Siswa ({{ $keterlambatan->count() }})</h3>
<table>
    <thead>
        <tr>
            <th style="width:28px">No</th>
            <th style="width:70px">Tanggal</th>
            <th>Nama Siswa</th>
            <th style="width:48px">Kelas</th>
            <th style="width:60px">Menit</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
    @forelse($keterlambatan as $i => $k)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td class="tengah">{{ \Carbon\Carbon::parse($k->tanggal)->isoFormat('D MMM Y') }}</td>
            <td>
                <span class="tebal">{{ $k->siswa->nama ?? '-' }}</span>
                <span class="sub">NISN: {{ $k->siswa->nisn ?? '-' }}</span>
            </td>
            <td class="tengah">{{ $k->siswa->kelas ?? '-' }}</td>
            <td class="tengah"><span class="pill pill-kuning">{{ $k->menit_terlambat }} mnt</span></td>
            <td>{{ $k->keterangan ?? '-' }}</td>
        </tr>
    @empty
        <tr class="kosong"><td colspan="6">Tidak ada data keterlambatan</td></tr>
    @endforelse
    </tbody>
</table>
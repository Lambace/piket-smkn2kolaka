<h3 class="judul-tabel">Tabel 4 — Data Izin Keluar ({{ $izinKeluar->count() }})</h3>
<table>
    <thead>
        <tr>
            <th style="width:28px">No</th>
            <th style="width:70px">Tanggal</th>
            <th>Nama Siswa</th>
            <th style="width:48px">Kelas</th>
            <th style="width:70px">Jenis</th>
            <th style="width:60px">Jam Keluar</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
    @forelse($izinKeluar as $i => $iz)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td class="tengah">{{ \Carbon\Carbon::parse($iz->tanggal)->isoFormat('D MMM Y') }}</td>
            <td>
                <span class="tebal">{{ $iz->siswa->nama ?? '-' }}</span>
                <span class="sub">NISN: {{ $iz->siswa->nisn ?? '-' }}</span>
            </td>
            <td class="tengah">{{ $iz->siswa->kelas ?? '-' }}</td>
            <td class="tengah"><span class="pill pill-biru">{{ $iz->jenis ?? '-' }}</span></td>
            <td class="tengah tebal">{{ $iz->jam_keluar ?? '-' }}</td>
            <td>{{ $iz->keterangan ?? '-' }}</td>
        </tr>
    @empty
        <tr class="kosong"><td colspan="7">Tidak ada data izin keluar</td></tr>
    @endforelse
    </tbody>
</table>
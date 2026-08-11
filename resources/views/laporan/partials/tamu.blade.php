<h3 class="judul-tabel">Tabel 6 — Buku Tamu ({{ $tamu->count() }})</h3>
<table>
    <thead><tr><th style="width:28px">No</th><th style="width:75px">Tanggal</th><th>Nama Tamu</th><th>Instansi</th><th>Keperluan</th><th style="width:60px">Jam Masuk</th><th style="width:60px">Jam Keluar</th></tr></thead>
    <tbody>
    @forelse($tamu as $i => $t)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td class="tengah">{{ \Carbon\Carbon::parse($t->tanggal_kunjungan)->isoFormat('D MMM Y') }}</td>
            <td>{{ $t->nama }}</td>
            <td>{{ $t->instansi ?: 'Umum' }}</td>
            <td>{{ $t->keperluan }}</td>
            <td class="tengah">{{ $t->jam_masuk ?? '-' }}</td>
            <td class="tengah">{{ $t->jam_keluar ?? 'Masih di sekolah' }}</td>
        </tr>
    @empty
        <tr class="kosong"><td colspan="7">Tidak ada kunjungan tamu</td></tr>
    @endforelse
    </tbody>
</table>
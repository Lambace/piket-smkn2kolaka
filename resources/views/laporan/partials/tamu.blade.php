<h3 class="judul-tabel">Tabel 6 — Buku Tamu ({{ $tamu->count() }})</h3>
<table>
    <thead>
        <tr>
            <th style="width:28px">No</th>
            <th style="width:70px">Tanggal</th>
            <th>Nama Tamu</th>
            <th>Instansi</th>
            <th>Keperluan</th>
            <th style="width:55px">Masuk</th>
            <th style="width:80px">Kondisi</th>
        </tr>
    </thead>
    <tbody>
    @forelse($tamu as $i => $t)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td class="tengah">{{ \Carbon\Carbon::parse($t->tanggal_kunjungan)->isoFormat('D MMM Y') }}</td>
            <td><span class="tebal">{{ $t->nama }}</span></td>
            <td>{{ $t->instansi ?: 'Umum' }}</td>
            <td>{{ $t->keperluan }}</td>
            <td class="tengah tebal">{{ $t->jam_masuk ?? '-' }}</td>
            <td class="tengah">
                @if($t->jam_keluar)
                    <span class="pill pill-hijau">Keluar {{ $t->jam_keluar }}</span>
                @else
                    <span class="pill pill-ungu">Di Sekolah</span>
                @endif
            </td>
        </tr>
    @empty
        <tr class="kosong"><td colspan="7">Tidak ada kunjungan tamu</td></tr>
    @endforelse
    </tbody>
</table>
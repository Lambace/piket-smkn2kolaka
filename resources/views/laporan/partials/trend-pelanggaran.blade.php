<h3 class="judul-tabel">Tabel 9 — Trend Pelanggaran Harian</h3>
<table>
    <thead><tr><th style="width:28px">No</th><th>Tanggal</th><th style="width:80px">Jumlah Pelanggaran</th></tr></thead>
    <tbody>
    @forelse($trend as $i => $d)
        <tr><td class="tengah">{{ $i+1 }}</td><td>{{ \Carbon\Carbon::parse($d->tanggal)->isoFormat('ddd, D MMM Y') }}</td><td class="tengah">{{ $d->jumlah }}</td></tr>
    @empty
        <tr class="kosong"><td colspan="3">Tidak ada data</td></tr>
    @endforelse
    </tbody>
</table>
<h3 class="judul-tabel">Tabel 10 — Status Pelanggaran</h3>
<table>
    <thead><tr><th style="width:28px">No</th><th>Status</th><th style="width:80px">Jumlah</th></tr></thead>
    <tbody>
    @forelse($statusPelanggaran as $i => $d)
        <tr><td class="tengah">{{ $i+1 }}</td><td>{{ ucfirst($d->label) }}</td><td class="tengah">{{ $d->jumlah }}</td></tr>
    @empty
        <tr class="kosong"><td colspan="3">Tidak ada data</td></tr>
    @endforelse
    </tbody>
</table>
<h3 class="judul-tabel">Tabel 8 — Keterlambatan per Jurusan</h3>
<table>
    <thead><tr><th style="width:28px">No</th><th>Jurusan</th><th style="width:80px">Jumlah Terlambat</th></tr></thead>
    <tbody>
    @forelse($perJurusan as $i => $d)
        <tr><td class="tengah">{{ $i+1 }}</td><td>{{ $d->label ?? 'Tanpa Jurusan' }}</td><td class="tengah">{{ $d->jumlah }}</td></tr>
    @empty
        <tr class="kosong"><td colspan="3">Tidak ada data</td></tr>
    @endforelse
    </tbody>
</table>
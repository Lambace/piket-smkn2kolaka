<h3 class="judul-tabel">Tabel 7 — Keterlambatan per Kelas</h3>
<table>
    <thead><tr><th style="width:28px">No</th><th>Kelas</th><th style="width:80px">Jumlah Terlambat</th></tr></thead>
    <tbody>
    @forelse($perKelas as $i => $d)
        <tr><td class="tengah">{{ $i+1 }}</td><td>{{ $d->label ?? 'Tanpa Kelas' }}</td><td class="tengah">{{ $d->jumlah }}</td></tr>
    @empty
        <tr class="kosong"><td colspan="3">Tidak ada data</td></tr>
    @endforelse
    </tbody>
</table>
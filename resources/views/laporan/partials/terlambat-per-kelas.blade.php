<h3 class="judul-tabel">Tabel 7 — Keterlambatan per Kelas</h3>
<table>
    <thead>
        <tr><th style="width:28px">No</th><th>Kelas</th><th style="width:120px">Jumlah Terlambat</th></tr>
    </thead>
    <tbody>
    @forelse($perKelas as $i => $d)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td><span class="tebal">{{ $d->label ?? 'Tanpa Kelas' }}</span></td>
            <td class="tengah"><span class="pill pill-kuning">{{ $d->jumlah }} kali</span></td>
        </tr>
    @empty
        <tr class="kosong"><td colspan="3">Tidak ada data</td></tr>
    @endforelse
    </tbody>
</table>
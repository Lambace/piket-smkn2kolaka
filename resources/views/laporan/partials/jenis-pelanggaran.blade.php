<h3 class="judul-tabel">Tabel 11 — Distribusi Jenis Pelanggaran</h3>
<table>
    <thead>
        <tr><th style="width:28px">No</th><th>Jenis Pelanggaran</th><th style="width:100px">Jumlah</th></tr>
    </thead>
    <tbody>
    @forelse($jenisPelanggaran as $i => $d)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td><span class="tebal">{{ $d->label }}</span></td>
            <td class="tengah"><span class="pill pill-ungu">{{ $d->jumlah }} kasus</span></td>
        </tr>
    @empty
        <tr class="kosong"><td colspan="3">Tidak ada data</td></tr>
    @endforelse
    </tbody>
</table>
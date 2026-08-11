<h3 class="judul-tabel">Tabel 9 — Trend Pelanggaran Harian</h3>
<table>
    <thead>
        <tr><th style="width:28px">No</th><th>Tanggal</th><th style="width:130px">Jumlah Pelanggaran</th></tr>
    </thead>
    <tbody>
    @forelse($trend as $i => $d)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td><span class="tebal">{{ \Carbon\Carbon::parse($d->tanggal)->isoFormat('ddd, D MMM Y') }}</span></td>
            <td class="tengah">
                <span class="pill {{ $d->jumlah > 0 ? 'pill-merah' : 'pill-hijau' }}">{{ $d->jumlah }} kasus</span>
            </td>
        </tr>
    @empty
        <tr class="kosong"><td colspan="3">Tidak ada data</td></tr>
    @endforelse
    </tbody>
</table>
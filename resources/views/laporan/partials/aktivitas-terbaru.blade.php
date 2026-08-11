<h3 class="judul-tabel">Tabel 12 — Aktivitas Terbaru</h3>
<table>
    <thead><tr><th style="width:28px">No</th><th style="width:110px">Waktu</th><th style="width:80px">Tipe</th><th>Keterangan</th></tr></thead>
    <tbody>
    @forelse($aktivitas as $i => $a)
        <tr>
            <td class="tengah">{{ $i+1 }}</td>
            <td>{{ \Carbon\Carbon::parse($a['waktu'])->isoFormat('D MMM, HH.mm') }}</td>
            <td class="tengah">{{ $a['tipe'] }}</td>
            <td>{{ $a['teks'] }}</td>
        </tr>
    @empty
        <tr class="kosong"><td colspan="4">Tidak ada aktivitas</td></tr>
    @endforelse
    </tbody>
</table>
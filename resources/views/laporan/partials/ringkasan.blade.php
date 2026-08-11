<h3 class="judul-tabel">Tabel 2 — Ringkasan Piket</h3>
<table>
    <thead>
        <tr><th style="width:45%">Indikator</th><th>Nilai</th></tr>
    </thead>
    <tbody>
    @foreach($ringkasan as $r)
        <tr>
            <td><span class="tebal">{{ $r['label'] }}</span></td>
            <td>{{ $r['nilai'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
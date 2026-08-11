<h3 class="judul-tabel">Tabel 2 — Ringkasan Piket</h3>
<table>
    <tbody>
    @foreach($ringkasan as $r)
        <tr><td style="width:40%"><strong>{{ $r['label'] }}</strong></td><td>{{ $r['nilai'] }}</td></tr>
    @endforeach
    </tbody>
</table>
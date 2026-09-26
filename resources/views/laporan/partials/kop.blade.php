<table class="kop-table">
    <tr>
        {{-- KOLOM KIRI: Logo Instansi --}}
        <td class="kop-logo">
            @if($logoInstansi ?? null)
                <img src="{{ $logoInstansi }}" alt="Logo Instansi">
            @endif
        </td>

        {{-- KOLOM TENGAH: Teks --}}
        <td class="kop-teks">
            <div class="kop-baris1">{{ $pengaturan->kop_baris1 ?? 'PEMERINTAH PROVINSI SULAWESI TENGGARA' }}</div>
            <div class="kop-baris2">{{ $pengaturan->kop_baris2 ?? 'DINAS PENDIDIKAN DAN KEBUDAYAAN' }}</div>
            <div class="kop-nama">
                {{ strtoupper(
                    $pengaturan->kop_nama_sekolah
                        ?: ($pengaturan->nama_sekolah ?? 'SEKOLAH MENENGAH KEJURUAN (SMK) NEGERI 2 KOLAKA')
                ) }}
            </div>
            <div class="kop-alamat">{{ $pengaturan->alamat ?? 'Jln. Poros Kolaka - Pomalaa KM. 16 Kec. Baula Kab. Kolaka Provinsi SULTRA' }}</div>
            <div class="kop-alamat">
                E-mail <span class="kop-link">{{ $pengaturan->email ?? 'smknsatubaula@yahoo.co.id' }}</span>
                &nbsp;&nbsp;Telp. {{ $pengaturan->telepon ?? '082346999111' }}
            </div>
            <div class="kop-alamat">
                Website: <span class="kop-link">{{ $pengaturan->website ?? 'www.smk1baula.sch.id' }}</span>
                &nbsp;&nbsp;Server: <span class="kop-link">{{ $pengaturan->server ?? 'sisfo.smk1baula.sch.id' }}</span>
            </div>
        </td>

        {{-- KOLOM KANAN: Logo Sekolah --}}
        <td class="kop-logo">
            @if($logo ?? null)
                <img src="{{ $logo }}" alt="Logo Sekolah">
            @endif
        </td>
    </tr>
</table>

{{-- Double line bawah kop --}}
<div class="kop-garis"></div>
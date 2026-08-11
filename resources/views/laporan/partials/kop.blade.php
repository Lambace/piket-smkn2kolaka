<div class="kop">
    <div class="kop-logo">
        @if($logo)<img src="{{ $logo }}" alt="Logo">@endif
    </div>
    <div class="kop-teks">
        <div class="kop-pemerintah">PEMERINTAH PROVINSI SULAWESI TENGGARA</div>
        <div class="kop-nama">{{ $pengaturan->nama_sekolah ?? 'SMK NEGERI 2 KOLAKA' }}</div>
        <div class="kop-alamat">
            {{ $pengaturan->alamat ?? 'Jl. Poros Kolaka–Pomalaa KM.16, Kec. Baula, Kab. Kolaka, Sulawesi Tenggara' }}
            @if($pengaturan->telepon ?? null) &bull; Telp: {{ $pengaturan->telepon }} @endif
            @if($pengaturan->email ?? null) &bull; Email: {{ $pengaturan->email }} @endif
        </div>
    </div>
</div>
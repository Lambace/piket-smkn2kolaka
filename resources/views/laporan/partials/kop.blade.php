<div class="kop">
    <div class="kop-logo">
        @if($logo)<img src="{{ $logo }}" alt="Logo">@endif
    </div>
    <div class="kop-teks">
        <p class="kop-sub">PEMERINTAH DAERAH</p>
        <p class="kop-nama">{{ $pengaturan->nama_sekolah ?? 'SMKN 2 KOLAKA' }}</p>
        <p class="kop-alamat">{{ $pengaturan->alamat ?? 'Jl. Pendidikan No. 1, Kolaka' }}</p>
    </div>
</div>
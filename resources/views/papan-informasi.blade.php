<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Papan Informasi Digital — {{ $pengaturan->nama_sekolah ?? 'Sistem Informasi Piket' }}</title>
<style>
  :root{--primary:#4f46e5;--dark:#0f172a;--slate:#475569;--light:#f8fafc;--green:#16a34a;--red:#dc2626;--amber:#d97706}
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:'Segoe UI',system-ui,Arial,sans-serif;color:var(--dark);background:#e2e8f0}
  .page{width:100%;max-width:210mm;min-height:297mm;margin:8mm auto;background:#fff;position:relative;overflow:hidden;page-break-after:always}
  @page{size:A4;margin:0}

  /* TOOLBAR (hilang saat cetak) */
  .toolbar{position:fixed;top:5mm;right:5mm;z-index:99;display:flex;gap:3mm}
  .btn{padding:2.5mm 5mm;border-radius:3mm;font-size:11px;font-weight:700;text-decoration:none;border:none;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.25)}
  .btn.back{background:#fff;color:#334155}
  .btn.back:hover{background:#f1f5f9}
  .btn.print{background:#16a34a;color:#fff}
  .btn.print:hover{background:#15803d}

  /* COVER */
  .cover{background:linear-gradient(160deg,#1e1b4b 0%,#312e81 45%,#4f46e5 100%);color:#fff;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;padding:20mm}
  .cover-logo{width:38mm;height:38mm;object-fit:contain;background:#fff;border-radius:8mm;padding:4mm;margin-bottom:6mm;box-shadow:0 4px 18px rgba(0,0,0,.35)}
  .cover-emoji{font-size:28mm;margin-bottom:6mm}
  .cover-badge{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.35);padding:2mm 6mm;border-radius:20mm;font-size:11px;letter-spacing:3px;margin-bottom:8mm}
  .cover h1{font-size:30px;line-height:1.25;letter-spacing:1px}
  .cover h1 span{color:#a5b4fc;font-size:20px}
  .cover-school{margin-top:6mm;font-size:16px;font-weight:700;color:#fde68a;letter-spacing:2px}
  .cover-tagline{margin-top:8mm;font-size:12px;font-style:italic;color:#e0e7ff;max-width:140mm}
  .cover-chips{margin-top:10mm;display:flex;flex-wrap:wrap;gap:3mm;justify-content:center}
  .cover-chips span{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.3);padding:2mm 5mm;border-radius:6mm;font-size:11px}
  .cover-footer{position:absolute;bottom:8mm;left:12mm;right:12mm;font-size:10px;color:#c7d2fe}
  /* HEAD & ISI */
  .page-head{background:linear-gradient(90deg,var(--primary),#0ea5e9);color:#fff;padding:8mm 12mm}
  .page-head h2{font-size:20px;letter-spacing:1px}
  .page-head p{font-size:11px;opacity:.9;margin-top:1mm}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:4mm;padding:8mm 12mm}
  .card{background:var(--light);border:1px solid #e2e8f0;border-left:1.5mm solid var(--primary);border-radius:3mm;padding:4mm 5mm}
  .card .ico{font-size:18px}
  .card h3{font-size:12px;margin:1.5mm 0 1mm;color:var(--primary)}
  .card p{font-size:10px;color:var(--slate);line-height:1.5}
  .cols{display:grid;grid-template-columns:1fr 1fr;gap:6mm;padding:8mm 12mm}
  .box{background:var(--light);border:1px solid #e2e8f0;border-radius:3mm;padding:5mm}
  .box h3{font-size:12px;color:var(--primary);margin-bottom:3mm;border-bottom:1px solid #e2e8f0;padding-bottom:2mm}
  .box ol,.box ul{margin-left:5mm;font-size:10px;color:var(--slate);line-height:1.7}
  .flow{margin:6mm 12mm 0;background:#eef2ff;border:1px dashed var(--primary);border-radius:3mm;padding:4mm;text-align:center;font-size:11px;color:#3730a3;font-weight:600}
  .foot{position:absolute;bottom:6mm;left:12mm;right:12mm;font-size:9px;color:#94a3b8;text-align:center;border-top:1px solid #e2e8f0;padding-top:3mm}
  .tv-url{background:#0f172a;color:#4ade80;font-family:monospace;font-size:10px;padding:3mm;border-radius:2mm;margin:2mm 0;word-break:break-all}

  /* ===== LAYAR KECIL (HP) ===== */
  @media (max-width:760px){
    .page{margin:0 auto 6mm;min-height:auto}
    .grid,.cols{grid-template-columns:1fr;gap:4mm;padding:6mm}
    .page-head{padding:6mm}
    .page-head h2{font-size:16px}
    .page-head p{font-size:10px}
    .cover{padding:14mm 8mm}
    .cover h1{font-size:22px}
    .cover h1 span{font-size:15px}
    .cover-logo{width:26mm;height:26mm}
    .cover-emoji{font-size:20mm}
    .cover-school{font-size:13px}
    .cover-tagline{font-size:11px}
    .toolbar{top:3mm;right:3mm}
    .btn{padding:2mm 4mm;font-size:10px}
    .flow{margin:4mm 6mm 0;font-size:10px}
    .foot{left:6mm;right:6mm}
  }

  /* ===== LAYAR BESAR: tinggi halaman mengikuti isi (hilangkan ruang kosong) ===== */
  @media screen and (min-width:761px){
    .page{min-height:auto}
    .cover{padding:18mm 20mm}
  }


  /* ===== SAAT DICETAK: KEMBALI A4 PENUH ===== */
  @media print{
    body{background:#fff}
    .toolbar{display:none!important}
    .page{margin:0;box-shadow:none;width:210mm;max-width:none;min-height:297mm}
    .grid,.cols{grid-template-columns:1fr 1fr;padding:8mm 12mm}
    .page-head{padding:8mm 12mm}
    .page-head h2{font-size:20px}
    .page-head p{font-size:11px}
    .cover{padding:20mm}
    .cover h1{font-size:30px}
    .cover h1 span{font-size:20px}
    .cover-logo{width:38mm;height:38mm}
    .cover-emoji{font-size:28mm}
    .cover-school{font-size:16px}
    .cover-tagline{font-size:12px}
    .flow{margin:6mm 12mm 0;font-size:11px}
    .foot{left:12mm;right:12mm}
  }
</style>
</head>
<body>

<!-- ============ TOOLBAR (Tidak ikut tercetak) ============ -->
<div class="toolbar">
    <a href="{{ route('dashboard') }}" class="btn back">← Dashboard</a>
    <button onclick="window.print()" class="btn print">🖨️ Cetak / Simpan PDF</button>
</div>

<!-- ============ HALAMAN 1 : SAMPUL ============ -->
<div class="page cover">
    @if(!empty($logoUrl))
        <img src="{{ $logoUrl }}" alt="Logo Sekolah" class="cover-logo">
    @else
        <div class="cover-emoji">🏫</div>
    @endif

   
    <h1>SISTEM INFORMASI PIKET<br><span>(SIPIKET)</span></h1>
    <p class="cover-school">{{ $pengaturan->nama_sekolah ?? 'SMKN 2 KOLAKA' }}</p>
    <p class="cover-tagline">"Tertib Tercatat, Otomatis Terkabar — Piket Lebih Mudah, Sekolah Lebih Tertib"</p>
    <div class="cover-chips">
        <span>📱 Notifikasi WA</span><span>📨 Rekap Otomatis</span><span>📊 Dashboard</span>
        <span>📺 Mode TV</span><span>📄 Laporan PDF/Excel</span><span>☁️ Berbasis Cloud</span>
    </div>
    <div class="cover-footer">Tahun Ajaran 2026/2027 • Dokumen Resmi Tim Piket</div>
</div>

<!-- ============ HALAMAN 2 : KEUNGGULAN ============ -->
<div class="page">
    <div class="page-head"><h2>🌟 KEUNGGULAN APLIKASI</h2><p>12 alasan SIPIKET membuat tugas piket lebih cepat, rapi, dan transparan</p></div>
    <div class="grid">
        <div class="card"><div class="ico">⚡</div><h3>Pencatatan Kilat</h3><p>Catat keterlambatan, izin keluar, pelanggaran, dan tamu dalam hitungan detik. Jam tercatat otomatis.</p></div>
        <div class="card"><div class="ico">📱</div><h3>WA Otomatis ke Orang Tua</h3><p>Orang tua langsung menerima notifikasi WhatsApp saat kejadian tercatat — tanpa perlu dihubungi manual.</p></div>
        <div class="card"><div class="ico">📨</div><h3>Rekap Harian ke Wali Kelas</h3><p>Setiap pukul 15.00, wali kelas menerima rekap kelasnya via WA secara otomatis. Hemat waktu & kuota.</p></div>
        <div class="card"><div class="ico">📊</div><h3>Dashboard Interaktif</h3><p>Grafik keterlambatan per kelas & jurusan, daftar poin tertinggi, dan aktivitas terbaru secara real-time.</p></div>
        <div class="card"><div class="ico">📺</div><h3>Mode TV Lobi</h3><p>Papan informasi publik dengan jam hidup & pembaruan otomatis tiap 60 detik untuk layar lobi sekolah.</p></div>
        <div class="card"><div class="ico">📄</div><h3>Laporan Lengkap</h3><p>Export PDF & Excel dengan periode harian, mingguan, bulanan, semester — lengkap dengan kolom tanda tangan.</p></div>
        <div class="card"><div class="ico">📸</div><h3>Bukti Digital</h3><p>Foto pelanggaran & foto KTP tamu tersimpan aman di penyimpanan cloud, tak akan hilang.</p></div>
        <div class="card"><div class="ico">🧮</div><h3>Sistem Poin Pelanggaran</h3><p>Poin tercatat otomatis per siswa; siswa berpoin tertinggi terdeteksi seketika untuk pembinaan BK.</p></div>
        <div class="card"><div class="ico">📥</div><h3>Import/Export Excel</h3><p>Ratusan data siswa masuk dalam hitungan menit lewat file Excel — tanpa ketik satu per satu.</p></div>
        <div class="card"><div class="ico">🎨</div><h3>Identitas Sekolah</h3><p>Logo, nama sekolah, dan warna tema dapat disesuaikan — tampilan aplikasi mengikuti identitas sekolah.</p></div>
        <div class="card"><div class="ico">☁️</div><h3>Data Aman di Cloud</h3><p>Database MySQL & storage cloud: data tidak hilang walau komputer rusak atau diganti.</p></div>
        <div class="card"><div class="ico">🔐</div><h3>Keamanan Berlapis</h3><p>Login per pengguna, tautan TV rahasia dengan kunci, dan penggantian password sekali klik.</p></div>
    </div>
    <div class="flow">ALUR KERJA: 📝 Dicatat Guru Piket → 📱 WA ke Orang Tua → 📨 Rekap 15.00 ke Wali Kelas → 📄 Laporan PDF ke Kepala Sekolah</div>
    <div class="foot">SIPIKET — Sistem Informasi Piket • Halaman 2</div>
</div>

<!-- ============ HALAMAN 3 : PETUNJUK PENGGUNAAN ============ -->
<div class="page">
    <div class="page-head"><h2>📘 PETUNJUK PENGGUNAAN</h2><p>Panduan praktis bagi guru piket & administrator</p></div>
    <div class="cols">
        <div class="box"><h3>🔑 A. Login</h3><ol><li>Buka alamat aplikasi di browser.</li><li>Masukkan email & password akun.</li><li>Klik <b>Login</b> — dashboard tampil.</li></ol></div>
        <div class="box"><h3>⏰ B. Catat Keterlambatan</h3><ol><li>Menu <b>Keterlambatan</b> → tombol catat.</li><li>Cari & pilih siswa (jam otomatis).</li><li>Isi keterangan → <b>Simpan</b>.</li><li>WA terkirim otomatis ke orang tua.</li></ol></div>
        <div class="box"><h3>🚪 C. Izin Keluar</h3><ol><li>Menu <b>Izin Keluar</b> → isi siswa, tujuan, keperluan.</li><li>Status aktif & <b>otomatis tertutup pukul 12.00</b>.</li></ol></div>
        <div class="box"><h3>⚠️ D. Pelanggaran</h3><ol><li>Menu <b>Pelanggaran</b> → pilih siswa & jenis.</li><li>Poin terisi otomatis; unggah foto bukti.</li><li><b>Simpan</b> → WA ke orang tua.</li></ol></div>
        <div class="box"><h3>📒 E. Buku Tamu</h3><ol><li>Menu <b>Buku Tamu</b> → isi identitas & keperluan.</li><li>Lampirkan foto KTP (opsional) → <b>Simpan</b>.</li></ol></div>
        <div class="box"><h3>🗂️ F. Data & Laporan</h3><ol><li><b>Siswa</b>: Import/Export Excel.</li><li><b>Laporan</b>: pilih jenis & periode → PDF/Excel.</li><li>Satu klik: tombol <b>Download Laporan</b> di Dashboard & TV (harian/bulanan/semester).</li></ol></div>
        <div class="box"><h3>⚙️ G. Pengaturan</h3><ul><li>Unggah logo sekolah (PNG/JPG/SVG).</li><li>Ubah nama sekolah & warna tema.</li><li>Klik <b>Simpan</b> — semua halaman menyesuaikan.</li></ul></div>
        <div class="box"><h3>🧑🏫 H. Wali Kelas & Wali Murid</h3><ul><li>Isi <b>nomor WA wali kelas</b> (penerima rekap 15.00).</li><li>Isi <b>nomor WA wali murid</b> (penerima notifikasi).</li></ul></div>
    </div>
    <div class="foot">SIPIKET — Sistem Informasi Piket • Halaman 3</div>
</div>

<!-- ============ HALAMAN 4 : MODE TV, PIHAK TERKAIT, KEAMANAN ============ -->
<div class="page">
    <div class="page-head"><h2>📺 MODE TV & INFORMASI PIHAK TERKAIT</h2><p>Pemasangan papan tampilan lobi serta peran wali kelas dan orang tua</p></div>
    <div class="cols">
        <div class="box"><h3>📺 Pemasangan TV Lobi</h3><ol>
            <li>Siapkan PC / Android Box terhubung TV di lobi.</li>
            <li>Buka browser, kunjungi:</li>
            <div class="tv-url">{{ config('app.url') }}/tampil?k={{ config('app.display_key', 'piket2026') }}</div>
            <li>Tekan <b>F11</b> agar layar penuh.</li>
            <li>Selesai — data & jam memperbarui diri sendiri tiap 60 detik.</li>
            <li>Tersedia tombol <b>Download Laporan</b> (harian/mingguan/bulanan/semester) bagi petugas berkepentingan.</li>
        </ol></div>
        <div class="box"><h3>👥 Peran Wali Kelas & Orang Tua</h3><ul>
            <li><b>Wali kelas</b>: menerima rekap harian kelas via WA setiap pukul 15.00 — tanpa perlu meminta data ke piket.</li>
            <li><b>Orang tua</b>: menerima notifikasi instan saat anak terlambat / izin keluar / tercatat pelanggaran.</li>
            <li><b>Kepala sekolah / BK</b>: memantau dashboard, grafik, dan laporan PDF kapan saja.</li>
        </ul></div>
        <div class="box"><h3>🔒 Keamanan & Tips</h3><ul>
            <li>Ganti password admin secara berkala.</li>
            <li>Jangan sebarkan tautan TV ke publik (kunci rahasia).</li>
            <li>Pantau menu <b>Notifikasi</b> untuk melihat log WA terkirim/gagal.</li>
            <li>Backup rutin tersedia di dashboard cloud.</li>
        </ul></div>
        <div class="box"><h3>🆘 Bantuan</h3><ul>
            <li>WA tidak masuk? Cek nomor wali murid & menu Notifikasi.</li>
            <li>Logo tidak tampil? Unggah ulang via Pengaturan.</li>
            <li>Lupa password? Hubungi administrator sistem.</li>
        </ul></div>
    </div>
    <div class="flow">💡 CETAK DOKUMEN INI SEBAGAI POSTER / BAGIKAN PDF KE DEWAN GURU & ORANG TUA</div>
    <div class="foot">© {{ date('Y') }} {{ $pengaturan->nama_sekolah ?? 'Tim Piket' }} — Dibangun dengan ❤️ untuk pendidikan Indonesia • Halaman 4</div>
</div>

</body>
</html>
<?php

use App\Http\Controllers\AbsensiPetugasController;
use App\Http\Controllers\BukuTamuController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IzinKeluarController;
use App\Http\Controllers\KeterlambatanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PelanggaranController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\UserPetugasController;
use App\Http\Controllers\WaliKelasController;
use App\Http\Controllers\WaliMuridController;
use App\Models\Pengaturan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// ===== ROUTE ROOT CERDAS =====
// Sudah login → langsung absensi | Belum login → halaman login
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('absensi.index');
    }
    return redirect()->route('login');
});

// ===== ROUTE PUBLIK (tanpa login) =====

// Papan informasi publik (tanpa login, tanpa sidebar)
Route::get('/tampil', [DashboardController::class, 'tampil'])->name('tampil');

// Download laporan PDF dari Mode Tampil (dilindungi kunci rahasia)
Route::get('/tampil/laporan', [LaporanController::class, 'pdf'])->name('tampil.laporan');

// ===== BARU: Daftar Hadir Piket dari Mode Tampil (publik + dilindungi key) =====
Route::get('/tampil/daftar-hadir', [LaporanController::class, 'daftarHadir'])->name('tampil.daftar-hadir');

// ===== LOGO SEKOLAH PUBLIK (URL pendek untuk Fonnte + embed di mana saja) =====
Route::get('/logo.png', function () {
    $p = Pengaturan::first();
    if (!$p || !$p->logo || !Storage::disk('public')->exists($p->logo)) {
        abort(404, 'Logo sekolah tidak ditemukan');
    }
    return response(Storage::disk('public')->get($p->logo), 200, [
        'Content-Type'  => Storage::disk('public')->mimeType($p->logo) ?: 'image/png',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('logo.sekolah');

// ===== LOGO INSTANSI PUBLIK (URL pendek untuk Fonnte) =====
Route::get('/logo-instansi.png', function () {
    $p = Pengaturan::first();
    if (!$p || !$p->logo_instansi || !Storage::disk('public')->exists($p->logo_instansi)) {
        abort(404, 'Logo instansi tidak ditemukan');
    }
    return response(Storage::disk('public')->get($p->logo_instansi), 200, [
        'Content-Type'  => Storage::disk('public')->mimeType($p->logo_instansi) ?: 'image/png',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('logo.instansi');

// ===== Papan Informasi Digital (publik, siap cetak PDF) =====
Route::get('/papan-informasi', function () {
    $pengaturan = Pengaturan::first();

    $logoUrl = null;
    if ($pengaturan && $pengaturan->logo && Storage::disk('public')->exists($pengaturan->logo)) {
        $logoUrl = route('logo.sekolah');
    }

    return view('papan-informasi', [
        'logoUrl'    => $logoUrl,
        'pengaturan' => $pengaturan,
    ]);
})->name('papan.informasi');

// ===== Serve file public disk (pengganti storage:link di Laravel Cloud) =====
// WAJIB DI ROUTE PUBLIK — dipakai oleh sidebar, form, preview, dll
Route::get('/storage/{path}', function (string $path) {
    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }
    return response(Storage::disk('public')->get($path), 200, [
        'Content-Type'  => Storage::disk('public')->mimeType($path) ?: 'application/octet-stream',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.*');

// ===== SEMUA USER LOGIN (Koordinator + Petugas) =====
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ===== ABSENSI PETUGAS (semua user — landing page setelah login) =====
    Route::get('absensi-petugas', [AbsensiPetugasController::class, 'index'])->name('absensi.index');
    Route::post('absensi-petugas', [AbsensiPetugasController::class, 'store'])->name('absensi.store');
    Route::delete('absensi-petugas/{id}', [AbsensiPetugasController::class, 'destroy'])->name('absensi.destroy');

    // Menu yang bisa diakses KOORDINATOR & PETUGAS
    Route::resource('wali-murid', WaliMuridController::class)->except(['create', 'show', 'edit']);
    Route::resource('keterlambatan', KeterlambatanController::class)->except(['create', 'show', 'edit']);
    Route::resource('izin-keluar', IzinKeluarController::class)->except(['create', 'show', 'edit']);
    Route::resource('buku-tamu', BukuTamuController::class)->except(['create', 'show', 'edit']);
    Route::resource('pelanggaran', PelanggaranController::class)->except(['create', 'show', 'edit']);

    // Laporan (PDF + Daftar Hadir)
    Route::get('laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('laporan/pdf', [LaporanController::class, 'pdf'])->name('laporan.pdf');
    // ===== BARU: Daftar Hadir Piket (login, ikut filter periode) =====
    Route::get('laporan/daftar-hadir', [LaporanController::class, 'daftarHadir'])->name('laporan.daftar-hadir');

    // ===== HANYA KOORDINATOR =====
    Route::middleware('role:koordinator')->group(function () {
        // Export & Import siswa
        Route::get('siswa/export', [SiswaController::class, 'export'])->name('siswa.export');
        Route::post('siswa/import', [SiswaController::class, 'import'])->name('siswa.import');
        Route::resource('siswa', SiswaController::class)->except(['create', 'show', 'edit']);

        Route::resource('wali-kelas', WaliKelasController::class)->except(['create', 'show', 'edit']);

        // Rekap harian otomatis ke wali kelas
        Route::post('/rekap/kirim', [WaliKelasController::class, 'kirimRekap'])->name('rekap.kirim');

        Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
        Route::post('/notifikasi/{id}/retry', [NotifikasiController::class, 'retry'])->name('notifikasi.retry');

        // Pengaturan aplikasi
        Route::get('pengaturan', [PengaturanController::class, 'edit'])->name('pengaturan.edit');
        Route::match(['post', 'patch'], 'pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');

        // Manajemen akun petugas
        Route::get('user-petugas', [UserPetugasController::class, 'index'])->name('user-petugas.index');
        Route::post('user-petugas', [UserPetugasController::class, 'store'])->name('user-petugas.store');
        Route::patch('user-petugas/{user}', [UserPetugasController::class, 'update'])->name('user-petugas.update');
        Route::post('user-petugas/{user}/reset-password', [UserPetugasController::class, 'resetPassword'])->name('user-petugas.reset-password');
        Route::delete('user-petugas/{user}', [UserPetugasController::class, 'destroy'])->name('user-petugas.destroy');
    });
});

// Routes profile bawaan Breeze
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
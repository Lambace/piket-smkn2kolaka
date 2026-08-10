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

// Halaman utama langsung redirect ke login
Route::get('/', fn () => redirect()->route('login'));

// ===== ROUTE PUBLIK =====

// Papan informasi publik (tanpa login, tanpa sidebar)
Route::get('/tampil', [DashboardController::class, 'tampil'])->name('tampil');

// Download laporan PDF dari Mode Tampil (dilindungi kunci rahasia)
Route::get('/tampil/laporan', [LaporanController::class, 'pdf'])->name('tampil.laporan');

// Papan Informasi Digital (publik, siap cetak PDF) — dinamis mengikuti Pengaturan
Route::get('/papan-informasi', function () {
    $pengaturan = Pengaturan::first();

    $logoUrl = null;
    if ($pengaturan && $pengaturan->logo) {
        try {
            $logoUrl = Storage::disk('public')->url($pengaturan->logo);
        } catch (\Throwable $e) {
            $logoUrl = asset('storage/' . $pengaturan->logo);
        }
    }

    return view('papan-informasi', [
        'logoUrl'    => $logoUrl,
        'pengaturan' => $pengaturan,
    ]);
})->name('papan.informasi');

// ===== SEMUA USER LOGIN (Koordinator + Petugas) =====

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Menu yang bisa diakses KOORDINATOR & PETUGAS
    Route::resource('wali-murid', WaliMuridController::class)->except(['create', 'show', 'edit']);
    Route::resource('keterlambatan', KeterlambatanController::class)->except(['create', 'show', 'edit']);
    Route::resource('izin-keluar', IzinKeluarController::class)->except(['create', 'show', 'edit']);
    Route::resource('buku-tamu', BukuTamuController::class)->except(['create', 'show', 'edit']);
    Route::resource('pelanggaran', PelanggaranController::class)->except(['create', 'show', 'edit']);

    // Laporan (Excel & PDF)
    Route::get('laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('laporan/excel', [LaporanController::class, 'excel'])->name('laporan.excel');
    Route::get('laporan/pdf', [LaporanController::class, 'pdf'])->name('laporan.pdf');

    // ===== HANYA KOORDINATOR =====
    Route::middleware('role:koordinator')->group(function () {
        // Absensi petugas piket
        Route::get('absensi-petugas', [AbsensiPetugasController::class, 'index'])->name('absensi.index');
        Route::post('absensi-petugas', [AbsensiPetugasController::class, 'store'])->name('absensi.store');
        Route::delete('absensi-petugas/{id}', [AbsensiPetugasController::class, 'destroy'])->name('absensi.destroy');

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
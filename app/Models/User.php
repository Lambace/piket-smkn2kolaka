<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

// ===== ATRIBUT MODEL: menempel tepat sebelum deklarasi class =====
#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'jenis_kelamin',
    'nip',
    'golongan',
    'status_kepegawaian',
    'hari_piket',
    'auto_hadir',      // ← agar toggle di form Akun Petugas bisa disimpan
    'no_wa',        // ← aktifkan setelah migration WA dijalankan
    'fonnte_token', // ← aktifkan setelah migration WA dijalankan
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Cast atribut ke tipe data native.
     * (Laravel 11 memakai method, BUKAN properti $casts)
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'auto_hadir'        => 'boolean', // ← badge JSX menerima true/false bersih
        ];
    }

    // ===== Helper Role =====
    public function isKoordinator(): bool
    {
        return $this->role === 'koordinator';
    }

    public function isPetugas(): bool
    {
        return $this->role === 'petugas';
    }

    // ===== Helper Data Pegawai =====
    public function getJenisKelaminLabelAttribute(): string
    {
        return match ($this->jenis_kelamin) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => '-',
        };
    }

    // ===== Relasi =====
    public function jadwalPiket(): HasMany
    {
        return $this->hasMany(JadwalPiket::class, 'user_id');
    }

    public function aktivitas(): HasMany
    {
        return $this->hasMany(Aktivitas::class, 'user_id');
    }
}
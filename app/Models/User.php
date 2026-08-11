<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'jenis_kelamin',
    'nip',
    'golongan',
    'status_kepegawaian',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
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

    public function jadwalPiket(): HasMany
    {
        return $this->hasMany(JadwalPiket::class, 'user_id');
    }

    public function aktivitas(): HasMany
    {
        return $this->hasMany(Aktivitas::class, 'user_id');
    }
}
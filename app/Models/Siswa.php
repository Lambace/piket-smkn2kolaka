<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Siswa extends Model
{
    use HasFactory;

    protected $table = 'siswa';

    protected $fillable = [
        'nisn', 'nis', 'nama', 'kelas', 'jurusan',
        'jenis_kelamin', 'alamat', 'telepon', 'foto', 'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function waliMurid(): HasMany
    {
        return $this->hasMany(WaliMurid::class, 'siswa_id');
    }

    // Wali utama yang menerima notifikasi WA
    public function waliUtama(): HasOne
    {
        return $this->hasOne(WaliMurid::class, 'siswa_id')->where('utama', true);
    }

    public function keterlambatan(): HasMany
    {
        return $this->hasMany(Keterlambatan::class, 'siswa_id');
    }

    public function izinKeluar(): HasMany
    {
        return $this->hasMany(IzinKeluar::class, 'siswa_id');
    }

    public function pelanggaran(): HasMany
    {
        return $this->hasMany(Pelanggaran::class, 'siswa_id');
    }
}
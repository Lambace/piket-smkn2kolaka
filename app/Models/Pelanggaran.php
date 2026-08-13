<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pelanggaran extends Model
{
    protected $fillable = [
        'siswa_id',
        'tanggal',
        'jenis_pelanggaran',
        'keterangan',
        'poin',
        'foto_bukti',
        'status',
        'petugas_id',
    ];

    // ===== BARU: cast tanggal agar otomatis sesuai APP_TIMEZONE =====
    protected $casts = [
        'tanggal' => 'datetime',  // ← kunci utama: Laravel pakai timezone aplikasi
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }
}
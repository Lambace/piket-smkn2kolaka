<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Keterlambatan extends Model
{
    use HasFactory;

    protected $table = 'keterlambatan';

    protected $fillable = [
        'siswa_id', 'tanggal', 'jam_datang', 'menit_terlambat',
        'keterangan', 'status', 'petugas_id',
    ];

    protected $casts = [
        'tanggal'         => 'datetime',  // ← ubah dari 'date' ke 'datetime'
        'menit_terlambat' => 'integer',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }
}
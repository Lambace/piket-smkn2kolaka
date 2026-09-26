<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalPiket extends Model
{
    use HasFactory;

    protected $table = 'jadwal_piket';

    protected $fillable = [
        'user_id', 'tanggal_piket', 'shift', 'catatan', 'hadir',
    ];

    protected $casts = [
        'tanggal_piket' => 'date',
        'hadir' => 'boolean',
    ];

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
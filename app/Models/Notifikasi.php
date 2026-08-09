<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notifikasi extends Model
{
    use HasFactory;

    protected $table = 'notifikasi';

    protected $fillable = [
        'jenis', 'penerima_tipe', 'penerima_id', 'nomor_tujuan',
        'pesan', 'status', 'pesan_error', 'terkirim_pada',
    ];

    protected $casts = [
        'terkirim_pada' => 'datetime',
    ];

    public function penerima(): MorphTo
    {
        return $this->morphTo();
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aktivitas extends Model
{
    use HasFactory;

    protected $table = 'aktivitas';

    protected $fillable = [
        'user_id', 'aksi', 'model_tipe', 'model_id',
        'keterangan', 'perubahan', 'alamat_ip',
    ];

    protected $casts = [
        'perubahan' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
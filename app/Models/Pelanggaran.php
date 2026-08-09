<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Pelanggaran extends Model
{
    use HasFactory;

    protected $table = 'pelanggaran';

    protected $fillable = [
        'siswa_id', 'tanggal', 'jenis_pelanggaran', 'keterangan',
        'poin', 'foto_bukti', 'status', 'petugas_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'poin' => 'integer',
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
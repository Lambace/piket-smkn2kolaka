<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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

    // Cast tanggal agar otomatis sesuai APP_TIMEZONE
    protected $casts = [
        'tanggal' => 'datetime',
    ];

    // Auto-append foto_url ke response JSON (untuk link "📷 Lihat" di JSX)
    protected $appends = ['foto_url'];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    // Accessor: generate URL publik dari foto bukti
    public function getFotoUrlAttribute(): ?string
    {
        if (!$this->foto_bukti) {
            return null;
        }
        return Storage::disk('public')->url($this->foto_bukti);
    }
}
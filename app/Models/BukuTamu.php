<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BukuTamu extends Model
{
    use HasFactory;

    protected $table = 'buku_tamu';

    protected $fillable = [
        'nama', 'telepon', 'instansi', 'keperluan', 'bertemu_dengan',
        'jam_masuk', 'jam_keluar', 'tanggal_kunjungan', 'catatan', 'foto_ktp',
    ];

    protected $casts = [
        'tanggal_kunjungan' => 'datetime',  // ← ubah dari 'date' ke 'datetime' (timezone-aware)
    ];

    // Auto-append foto_ktp_url ke response JSON (untuk link "📷 Lihat" di JSX)
    protected $appends = ['foto_ktp_url'];

    public function getFotoKtpUrlAttribute(): ?string
    {
        if (!$this->foto_ktp) return null;
        return Storage::disk('public')->url($this->foto_ktp);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IzinKeluar extends Model
{
    use HasFactory;

    protected $table = 'izin_keluar';

    protected $fillable = [
        'siswa_id', 'tanggal', 'jam_keluar', 'jam_kembali',
        'keterangan', 'jenis', 'status', 'disetujui_oleh', 'disetujui_pada',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'disetujui_pada' => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    /**
     * Otomatis menutup izin keluar yang belum diisi jam kembalinya
     * setelah jam 12:00 siang (status -> kembali, jam kembali 12:00).
     */
    public static function tutupOtomatis(): void
    {
        // Hanya aktif setelah pukul 12:00
        if (now()->hour < 12) {
            return;
        }

        static::whereNull('jam_kembali')
            ->whereNotIn('status', ['kembali', 'ditolak'])
            ->whereDate('tanggal', '<=', now()->toDateString())
            ->update([
                'status' => 'kembali',
                'jam_kembali' => '12:00',
            ]);
    }
}
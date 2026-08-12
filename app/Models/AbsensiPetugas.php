<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsensiPetugas extends Model
{
    protected $table = 'absensi_petugas';

    protected $fillable = ['nama', 'jabatan', 'tanggal', 'jam_masuk', 'status', 'keterangan'];

    protected $casts = ['tanggal' => 'date'];
}
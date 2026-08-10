<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsensiPetugas extends Model
{
    protected $table = 'absensi_petugas';

    protected $fillable = ['tanggal', 'nama', 'jabatan', 'jam_masuk', 'status'];

    protected $casts = ['tanggal' => 'date'];
}
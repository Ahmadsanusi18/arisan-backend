<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogPengingat extends Model
{
    protected $fillable = [
        'jadwal_pembayaran_id', 'kloter_id', 'jenis',
        'waktu_kirim', 'status_kirim',
    ];

    protected $casts = [
        'waktu_kirim' => 'datetime',
    ];
}

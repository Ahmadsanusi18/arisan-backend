<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogNotifikasiAdminArisan extends Model
{
    protected $table = 'log_notifikasi_admin_arisan';

    protected $fillable = [
        'kloter_id',
        'user_id',
        'jenis',
        'tanggal_target',
        'waktu_kirim',
        'status_kirim',
    ];

    protected $casts = [
        'tanggal_target' => 'date',
        'waktu_kirim' => 'datetime',
    ];

    public function kloter()
    {
        return $this->belongsTo(Kloter::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalPembayaran extends Model
{
    protected $fillable = ['kloter_peserta_id', 'tanggal_jatuh_tempo', 'status_bayar'];

    protected $casts = [
        'tanggal_jatuh_tempo' => 'date',
    ];

    public function kloterPeserta()
    {
        return $this->belongsTo(KloterPeserta::class);
    }
}

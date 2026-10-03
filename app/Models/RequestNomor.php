<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestNomor extends Model
{
    protected $fillable = [
        'kloter_id', 'nomor_urut', 'peserta_nama',
        'peserta_nomor_wa', 'waktu_request', 'status',
    ];

    protected $casts = [
        'waktu_request' => 'datetime',
    ];

    public function kloter()
    {
        return $this->belongsTo(Kloter::class);
    }
}

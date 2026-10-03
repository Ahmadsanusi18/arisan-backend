<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kloter extends Model
{
    protected $fillable = [
        'nama',
        'total_nilai_arisan',
        'jumlah_peserta_maks',
        'interval_pembayaran',
        'durasi_pembayaran_hari',
        'tanggal_mulai',
        'biaya_admin',
        'kode_qr',
        'status',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
    ];

    public function requestNomors()
    {
        return $this->hasMany(RequestNomor::class);
    }

    public function pesertaFix()
    {
        return $this->hasMany(KloterPeserta::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KloterPeserta extends Model
{
    protected $fillable = ['kloter_id', 'peserta_id', 'nomor_urut', 'sumber'];

    protected $appends = ['nominal_pembayaran']; 
    
    public function kloter()
    {
        return $this->belongsTo(Kloter::class);
    }
    public function getNominalPembayaranAttribute()
    {
        if (!$this->kloter || !$this->kloter->jumlah_peserta_maks) {
            return 0;
        }

        return intdiv(
            $this->kloter->total_nilai_arisan,
            $this->kloter->jumlah_peserta_maks
        );
    }
    public function peserta()
    {
        
        return $this->belongsTo(Peserta::class);
    }

    public function jadwalPembayaran()
    {
        return $this->hasMany(JadwalPembayaran::class);
    }
}

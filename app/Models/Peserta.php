<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Peserta extends Model
{
    protected $fillable = ['nama', 'nomor_wa'];

    public function slotKloter()
    {
        return $this->hasMany(KloterPeserta::class);
    }
}

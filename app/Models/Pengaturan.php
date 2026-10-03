<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $fillable = [
        'jam_kirim_pengingat_peserta',
        'template_pesan_pengingat',
    ];
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppBranding extends Model
{
    protected $fillable = [
        'app_name',
        'logo_path',
        'primary_color',
        'sidebar_color',
        'accent_color',
    ];
}
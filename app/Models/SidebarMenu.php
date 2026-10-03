<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SidebarMenu extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'template',
        'access',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}
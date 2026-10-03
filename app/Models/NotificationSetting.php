<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'notifications_enabled',
        'whatsapp_enabled',
        'payment_reminder_enabled',
        'new_participant_enabled',
        'qr_approval_enabled',
        'sound_enabled',
    ];

    protected $casts = [
        'notifications_enabled' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'payment_reminder_enabled' => 'boolean',
        'new_participant_enabled' => 'boolean',
        'qr_approval_enabled' => 'boolean',
        'sound_enabled' => 'boolean',
    ];
}
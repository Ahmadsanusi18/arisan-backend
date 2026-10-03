<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use Illuminate\Http\Request;

class NotificationSettingController extends Controller
{
    public function index()
    {
        $settings = NotificationSetting::first();

        if (!$settings) {
            $settings = NotificationSetting::create([
                'notifications_enabled' => true,
                'whatsapp_enabled' => true,
                'payment_reminder_enabled' => true,
                'new_participant_enabled' => true,
                'qr_approval_enabled' => true,
                'sound_enabled' => true,
            ]);
        }

        return response()->json($settings);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'notifications_enabled' => ['required', 'boolean'],
            'whatsapp_enabled' => ['required', 'boolean'],
            'payment_reminder_enabled' => ['required', 'boolean'],
            'new_participant_enabled' => ['required', 'boolean'],
            'qr_approval_enabled' => ['required', 'boolean'],
            'sound_enabled' => ['required', 'boolean'],
        ]);

        $settings = NotificationSetting::first();

        if (!$settings) {
            $settings = NotificationSetting::create($data);
        } else {
            $settings->update($data);
        }

        return response()->json([
            'message' => 'Pengaturan notifikasi berhasil diperbarui.',
            'settings' => $settings->fresh(),
        ]);
    }
}
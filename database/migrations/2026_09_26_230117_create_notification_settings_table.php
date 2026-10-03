<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();

            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('whatsapp_enabled')->default(true);
            $table->boolean('payment_reminder_enabled')->default(true);
            $table->boolean('new_participant_enabled')->default(true);
            $table->boolean('qr_approval_enabled')->default(true);
            $table->boolean('sound_enabled')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
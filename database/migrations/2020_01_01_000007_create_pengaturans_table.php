<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturans', function (Blueprint $table) {
            $table->id();
            $table->time('jam_kirim_pengingat_peserta')->default('09:00:00');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturans');
    }
};

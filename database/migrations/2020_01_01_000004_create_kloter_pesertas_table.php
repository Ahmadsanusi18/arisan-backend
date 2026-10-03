<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kloter_pesertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kloter_id')->constrained('kloters')->cascadeOnDelete();
            $table->foreignId('peserta_id')->constrained('pesertas')->cascadeOnDelete();
            $table->unsignedInteger('nomor_urut');
            $table->enum('sumber', ['manual', 'qr_approval'])->default('manual');
            $table->timestamps();

            // Mekanisme lock: 1 nomor urut cuma boleh fix 1x per kloter
            $table->unique(['kloter_id', 'nomor_urut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kloter_pesertas');
    }
};

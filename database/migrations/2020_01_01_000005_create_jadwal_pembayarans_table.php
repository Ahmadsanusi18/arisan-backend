<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_pembayarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kloter_peserta_id')->constrained('kloter_pesertas')->cascadeOnDelete();
            $table->date('tanggal_jatuh_tempo');
            $table->enum('status_bayar', ['sudah', 'belum'])->default('belum');
            $table->timestamps();

            $table->index('tanggal_jatuh_tempo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_pembayarans');
    }
};

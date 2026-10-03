<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_nomors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kloter_id')->constrained('kloters')->cascadeOnDelete();
            $table->unsignedInteger('nomor_urut');
            $table->string('peserta_nama');
            $table->string('peserta_nomor_wa');
            $table->timestamp('waktu_request')->useCurrent();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

            $table->index(['kloter_id', 'nomor_urut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_nomors');
    }
};

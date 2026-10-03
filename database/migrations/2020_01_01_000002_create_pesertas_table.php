<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesertas', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('nomor_wa');
            $table->timestamps();

            $table->index('nomor_wa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesertas');
    }
};
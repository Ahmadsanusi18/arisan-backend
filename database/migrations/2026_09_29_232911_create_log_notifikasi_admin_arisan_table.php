<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_notifikasi_admin_arisan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kloter_id')
                ->constrained('kloters')
                ->cascadeOnDelete();

            $table->string('jenis', 30);

            // Untuk notifikasi pemenang harian.
            // Untuk kloter penuh nilainya boleh null.
            $table->date('tanggal_target')->nullable();

            $table->dateTime('waktu_kirim')->nullable();

            $table->string('status_kirim', 20);

            $table->timestamps();

            $table->unique(
                ['kloter_id', 'jenis', 'tanggal_target'],
                'log_notif_admin_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_notifikasi_admin_arisan');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_pengingats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_pembayaran_id')->nullable()->constrained('jadwal_pembayarans')->nullOnDelete();
            $table->foreignId('kloter_id')->nullable()->constrained('kloters')->nullOnDelete();
            $table->string('jenis'); // H-1, H, list_telat, konfirmasi_admin
            $table->timestamp('waktu_kirim')->nullable();
            $table->string('status_kirim'); // pending, sukses, gagal
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_pengingats');
    }
};

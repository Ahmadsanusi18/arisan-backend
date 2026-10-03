<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kloters', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->unsignedBigInteger('total_nilai_arisan');
            $table->unsignedInteger('jumlah_peserta_maks');
            $table->enum('interval_pembayaran', ['mingguan', 'bulanan'])->default('mingguan');
            $table->date('tanggal_mulai');
            $table->unsignedBigInteger('biaya_admin')->default(0);
            $table->string('kode_qr')->unique();
            $table->enum('status', ['aktif', 'selesai'])->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kloters');
    }
};

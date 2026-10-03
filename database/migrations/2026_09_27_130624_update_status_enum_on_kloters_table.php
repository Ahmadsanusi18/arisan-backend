<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kloters', function (Blueprint $table) {
            $table->enum('status', [
                'aktif',
                'terlambat',
                'selesai',
            ])->default('aktif')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kloters', function (Blueprint $table) {
            $table->enum('status', [
                'aktif',
                'selesai',
            ])->default('aktif')->change();
        });
    }
};
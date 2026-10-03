<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kloters', function (Blueprint $table) {
            $table->unsignedInteger('durasi_pembayaran_hari')
                ->nullable()
                ->after('interval_pembayaran');
        });
    }

    public function down(): void
    {
        Schema::table('kloters', function (Blueprint $table) {
            $table->dropColumn('durasi_pembayaran_hari');
        });
    }
};
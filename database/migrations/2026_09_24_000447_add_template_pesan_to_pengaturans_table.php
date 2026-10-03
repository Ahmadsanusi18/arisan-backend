<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->text('template_pesan_pengingat')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->dropColumn('template_pesan_pengingat');
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sidebar_menus', function (Blueprint $table) {
            $table->id();

            // Nama yang tampil di sidebar
            $table->string('name');

            // URL internal, contoh: anggota
            $table->string('slug')->unique();

            // Nama icon yang akan dipakai frontend
            $table->string('icon')->nullable();

            // Jenis template halaman
            // Contoh: table, form, dashboard
            $table->string('template')->default('table');

            // Hak akses menu
            // all = admin + superadmin
            // superadmin = superadmin saja
            $table->string('access')->default('all');

            // Urutan menu di sidebar
            $table->unsignedInteger('sort_order')->default(0);

            // Apakah menu sedang ditampilkan
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sidebar_menus');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_brandings', function (Blueprint $table) {
            $table->id();

            $table->string('app_name')->default('Arisan');
            $table->string('logo_path')->nullable();

            $table->string('primary_color')->default('#0d41e1');
            $table->string('sidebar_color')->default('#004A7C');
            $table->string('accent_color')->default('#ffc300');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_brandings');
    }
};
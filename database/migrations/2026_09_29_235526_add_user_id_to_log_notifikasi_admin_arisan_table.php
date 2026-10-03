<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('log_notifikasi_admin_arisan', function (Blueprint $table) {
            $table->dropUnique('log_notif_admin_unique');

            $table->unique(
                [
                    'kloter_id',
                    'jenis',
                    'tanggal_target',
                    'user_id',
                ],
                'log_notif_admin_unique'
            );

            $table->foreign('kloter_id')
                ->references('id')
                ->on('kloters')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('log_notifikasi_admin_arisan', function (Blueprint $table) {
            $table->dropForeign(['kloter_id']);
            $table->dropForeign(['user_id']);

            $table->dropUnique('log_notif_admin_unique');

            $table->unique(
                [
                    'kloter_id',
                    'jenis',
                    'tanggal_target',
                ],
                'log_notif_admin_unique'
            );
        });
    }
};
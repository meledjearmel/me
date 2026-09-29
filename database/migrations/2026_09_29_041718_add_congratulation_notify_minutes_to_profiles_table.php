<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Réglage global : au plus une notification push de félicitations par motif
     * sur ce nombre de minutes (0 = une notification à chaque envoi).
     */
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->unsignedSmallInteger('congratulation_notify_minutes')->default(10);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('congratulation_notify_minutes');
        });
    }
};

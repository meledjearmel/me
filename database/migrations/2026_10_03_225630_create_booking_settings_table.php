<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Réglages de la prise de rendez-vous (une seule ligne) : ouverte ou non, délai
     * minimum, horizon, pause entre deux rendez-vous et lien visio par défaut.
     * C'est aussi elle qui porte l'agenda (schedules de Zap).
     */
    public function up(): void
    {
        Schema::create('booking_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->unsignedSmallInteger('min_notice_hours')->default(24);
            $table->unsignedSmallInteger('horizon_days')->default(30);
            $table->unsignedSmallInteger('buffer_minutes')->default(15);
            $table->string('video_link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_settings');
    }
};

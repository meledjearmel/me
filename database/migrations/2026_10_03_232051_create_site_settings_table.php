<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Réglages de gestion du site (une seule ligne). Elle porte aussi mon agenda de
     * rendez-vous (schedules de Zap).
     */
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            // Site : le bouton « Contact » ouvre le tiroir latéral (vrai) ou mène à la page Contact (faux).
            $table->boolean('contact_opens_drawer')->default(true);
            // Avis : les visiteurs peuvent joindre ou filmer une vidéo.
            $table->boolean('testimonial_video_enabled')->default(true);
            // CV : le profil métier proposé au téléchargement, et la source prioritaire.
            $table->foreignId('cv_job_profile_id')->nullable()->constrained('job_profiles')->nullOnDelete();
            $table->string('cv_source')->default('uploaded');
            // Notifications : une notification de félicitations au plus toutes les N minutes.
            $table->unsignedSmallInteger('congratulation_notify_minutes')->default(10);
            // Rendez-vous.
            $table->boolean('booking_enabled')->default(false);
            $table->unsignedSmallInteger('booking_min_notice_hours')->default(24);
            $table->unsignedSmallInteger('booking_horizon_days')->default(30);
            $table->unsignedSmallInteger('booking_buffer_minutes')->default(15);
            $table->string('booking_video_link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};

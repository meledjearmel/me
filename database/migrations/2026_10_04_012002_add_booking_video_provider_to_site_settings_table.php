<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Visio des rendez-vous : un lien Jitsi unique créé pour chaque rendez-vous
     * (`jitsi`), ou mon lien fixe (`link`, booking_video_link).
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('booking_video_provider')->default('jitsi')->after('booking_buffer_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('booking_video_provider');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le rapport de la semaine, envoyé par email chaque lundi : activé ou non, et la date du
     * dernier envoi (pour ne jamais l'envoyer deux fois la même semaine).
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('weekly_report_enabled')->default(true);
            $table->timestamp('weekly_report_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['weekly_report_enabled', 'weekly_report_sent_at']);
        });
    }
};

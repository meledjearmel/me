<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * L'estimation affichée au visiteur quand la demande vient de l'estimateur de projet,
     * figée au moment de l'envoi (même si la grille change ensuite), et l'analyse de l'IA
     * qui aide à chiffrer la demande avant de répondre.
     */
    public function up(): void
    {
        Schema::table('engagements', function (Blueprint $table) {
            $table->json('estimate')->nullable()->after('timeline');
            $table->json('ai_analysis')->nullable()->after('estimate');
            $table->timestamp('ai_analyzed_at')->nullable()->after('ai_analysis');
        });

        Schema::table('site_settings', function (Blueprint $table) {
            // {"CHF": 90, "EUR": 85, "USD": 95, "XOF": 30000} : une devise sans taux n'est pas proposée.
            $table->json('estimator_hourly_rates')->nullable();
            // Marge pour imprévus ajoutée aux heures, incluse sans être détaillée au visiteur.
            $table->unsignedTinyInteger('estimator_risk_percent')->default(20);
            // Heures facturables par semaine, pour convertir les heures en délai.
            $table->unsignedTinyInteger('estimator_hours_per_week')->default(30);
        });
    }

    public function down(): void
    {
        Schema::table('engagements', function (Blueprint $table) {
            $table->dropColumn(['estimate', 'ai_analysis', 'ai_analyzed_at']);
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['estimator_hourly_rates', 'estimator_risk_percent', 'estimator_hours_per_week']);
        });
    }
};

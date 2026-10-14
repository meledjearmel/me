<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le taux horaire d'un élément de l'estimateur : ses propres taux par devise s'il en a,
     * sinon ceux de sa grille tarifaire, sinon le taux global des réglages.
     */
    public function up(): void
    {
        Schema::table('estimator_items', function (Blueprint $table) {
            $table->foreignId('estimator_rate_id')->nullable()->after('billing')->constrained()->nullOnDelete();
            $table->json('hourly_rates')->nullable()->after('estimator_rate_id');
        });
    }

    public function down(): void
    {
        Schema::table('estimator_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('estimator_rate_id');
            $table->dropColumn('hourly_rates');
        });
    }
};

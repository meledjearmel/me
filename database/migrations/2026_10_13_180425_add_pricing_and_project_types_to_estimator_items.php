<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Une option de l'estimateur peut ajouter un pourcentage des heures du projet plutôt
     * que des heures fixes, et être réservée à certains types de projet (sans lien, elle
     * est proposée pour tous).
     */
    public function up(): void
    {
        Schema::table('estimator_items', function (Blueprint $table) {
            // hours (heures min/max) ou percent (pourcentage des heures du projet).
            $table->string('pricing')->default('hours')->after('hours_max');
            $table->unsignedSmallInteger('percent')->nullable()->after('pricing');
        });

        Schema::create('estimator_feature_project_type', function (Blueprint $table) {
            $table->foreignId('feature_id')->constrained('estimator_items')->cascadeOnDelete();
            $table->foreignId('project_type_id')->constrained('estimator_items')->cascadeOnDelete();
            $table->primary(['feature_id', 'project_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimator_feature_project_type');

        Schema::table('estimator_items', function (Blueprint $table) {
            $table->dropColumn(['pricing', 'percent']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les simulations de l'estimateur : une ligne par visiteur anonyme (empreinte du jour), par
     * jour et par configuration (type de projet et options), avec la dernière fourchette vue.
     * Elles complètent l'entonnoir : visite de la page, simulation, demande envoyée.
     */
    public function up(): void
    {
        Schema::create('estimate_simulations', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_hash', 64);
            // Type de projet et options, identifiants triés : une même configuration ne compte qu'une fois par jour.
            $table->string('signature', 64);
            $table->foreignId('project_type_id')->nullable()->constrained('estimator_items')->nullOnDelete();
            $table->string('project_type');
            $table->json('features');
            $table->string('currency', 3);
            $table->unsignedInteger('price_min');
            $table->unsignedInteger('price_max');
            $table->timestamp('created_at')->nullable()->index();

            $table->unique(['visitor_hash', 'signature']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimate_simulations');
    }
};

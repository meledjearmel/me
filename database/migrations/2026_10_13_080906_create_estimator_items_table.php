<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La grille de l'estimateur de projet : types de projet (la base) et options qui
     * s'y ajoutent, chacun chiffré en heures de travail. Le prix découle du taux horaire
     * et de la marge de risque des réglages du site.
     */
    public function up(): void
    {
        Schema::create('estimator_items', function (Blueprint $table) {
            $table->id();
            // project_type ou feature.
            $table->string('kind');
            $table->json('name');
            $table->json('description')->nullable();
            $table->unsignedSmallInteger('hours_min');
            $table->unsignedSmallInteger('hours_max');
            // Types de projet seulement : fixed (forfait estimé) ou hourly (facturé à l'heure).
            $table->string('billing')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimator_items');
    }
};

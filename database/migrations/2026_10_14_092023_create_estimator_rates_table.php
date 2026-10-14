<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les grilles tarifaires de l'estimateur : des taux horaires nommés (ex. « Développement
     * avancé »), par devise, que partagent les types de projet et options qui y sont rattachés.
     */
    public function up(): void
    {
        Schema::create('estimator_rates', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            // Taux horaire par devise ; une devise absente suit le taux global des réglages.
            $table->json('hourly_rates');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimator_rates');
    }
};

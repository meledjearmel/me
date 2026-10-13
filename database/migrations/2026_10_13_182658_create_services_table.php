<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les offres de la page « Services » : ce que je propose, ce qui est compris et un prix
     * « à partir de » (calculé par l'estimateur, saisi à la main, ou « sur devis »), reliées
     * aux projets qui les illustrent.
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('tagline')->nullable();
            $table->json('description')->nullable();
            // Ce qui est compris, une ligne par élément.
            $table->json('includes')->nullable();
            // estimator (depuis le type de projet lié), manual (montants saisis) ou quote (sur devis).
            $table->string('price_mode')->default('quote');
            $table->foreignId('estimator_item_id')->nullable()->constrained('estimator_items')->nullOnDelete();
            // {"CHF": 3000, "EUR": 2800} : prix « à partir de » saisis par devise.
            $table->json('manual_prices')->nullable();
            $table->string('status')->default('draft');
            // Client IA qui a rédigé le brouillon via le serveur MCP (« Claude Code »…).
            $table->string('drafted_by', 60)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('project_service', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_service');
        Schema::dropIfExists('services');
    }
};

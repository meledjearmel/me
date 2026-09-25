<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            // Texte détaillé (traduit), affiché dans la fenêtre qui s'ouvre au clic sur la carte.
            $table->json('details')->nullable()->after('description');
        });

        // Technologies liées à une compétence : leurs logos s'affichent sur la carte.
        Schema::create('skill_technology', function (Blueprint $table) {
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technology_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->primary(['skill_id', 'technology_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skill_technology');

        Schema::table('skills', function (Blueprint $table) {
            $table->dropColumn('details');
        });
    }
};

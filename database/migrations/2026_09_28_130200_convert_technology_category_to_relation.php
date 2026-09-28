<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remplace la colonne `category` (l'ancien enum figé) par `category_id`, une relation vers
     * technology_categories : les catégories deviennent éditables depuis l'admin sans redéploiement.
     * Le rattachement des technologies déjà en base se fait ici, par correspondance de `key`
     * (identique à l'ancienne valeur de l'enum), pour ne dépendre d'aucun reseed manuel.
     */
    public function up(): void
    {
        Schema::table('technologies', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('category')->constrained('technology_categories')->cascadeOnDelete();
        });

        DB::statement('
            update technologies
            set category_id = (select id from technology_categories where technology_categories.key = technologies.category)
        ');

        Schema::table('technologies', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable(false)->change();
            $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technologies', function (Blueprint $table) {
            $table->string('category')->nullable()->after('name');
        });

        DB::statement('
            update technologies
            set category = (select key from technology_categories where technology_categories.id = technologies.category_id)
        ');

        Schema::table('technologies', function (Blueprint $table) {
            $table->string('category')->nullable(false)->change();
            $table->dropConstrainedForeignId('category_id');
        });
    }
};

<?php

use Database\Seeders\ProjectSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Préremplit l'étude de cas des projets déjà en base (la production n'est pas
 * réensemencée) ; les champs déjà saisis dans l'admin sont laissés tels quels.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        (new ProjectSeeder)->fillCaseStudies();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

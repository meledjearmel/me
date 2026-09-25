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
        Schema::table('profiles', function (Blueprint $table) {
            // Identité telle qu'elle figure sur le CV (le site affiche le nom d'usage).
            $table->string('cv_last_name')->nullable()->after('name');
            $table->string('cv_first_name')->nullable()->after('cv_last_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['cv_last_name', 'cv_first_name']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dépôt GitHub du projet (« propriétaire/nom »), privé compris : relie le projet à
     * l'activité synchronisée sans rien publier de plus que `repo_url`.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('github_repository')->nullable()->after('repo_url');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('github_repository');
        });
    }
};

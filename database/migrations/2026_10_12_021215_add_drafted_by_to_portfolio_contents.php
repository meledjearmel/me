<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $tables = ['experiences', 'educations', 'certifications', 'uses_items', 'skills'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Client IA qui a rédigé le brouillon (nom du jeton MCP), comme pour les projets et les articles.
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('drafted_by', 60)->nullable()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('drafted_by');
            });
        }
    }
};

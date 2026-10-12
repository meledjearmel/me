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
        // Client IA qui a rédigé le brouillon (nom du jeton MCP : « Claude Code », « Codex »…).
        foreach (['projects', 'posts'] as $table) {
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
        foreach (['projects', 'posts'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('drafted_by');
            });
        }
    }
};

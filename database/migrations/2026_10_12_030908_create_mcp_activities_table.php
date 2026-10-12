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
        // Journal des appels au serveur MCP (outils et commandes), gardé 90 jours. Le contenu
        // envoyé n'y figure pas : seulement qui, quoi, sur quel contenu, résultat et durée.
        Schema::create('mcp_activities', function (Blueprint $table) {
            $table->id();
            $table->string('client', 60);
            // tool ou prompt.
            $table->string('kind', 10);
            $table->string('name', 60);
            $table->string('target_type', 40)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->boolean('success');
            $table->string('error', 500)->nullable();
            $table->unsignedInteger('duration_ms');
            $table->timestamp('created_at')->index();

            $table->index(['client', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mcp_activities');
    }
};

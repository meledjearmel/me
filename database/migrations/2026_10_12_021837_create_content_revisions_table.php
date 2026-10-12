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
        // Modification d'un contenu publié proposée par l'IA (serveur MCP), appliquée seulement après relecture.
        Schema::create('content_revisions', function (Blueprint $table) {
            $table->id();
            // project, post, experience… ou now_page (sans identifiant).
            $table->string('content_type', 40);
            $table->unsignedBigInteger('content_id')->nullable();
            // {"fr": {champ: valeur}, "en": {champ: valeur}} : seuls les champs modifiés.
            $table->json('changes');
            $table->text('summary');
            $table->string('drafted_by', 60)->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['content_type', 'content_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_revisions');
    }
};

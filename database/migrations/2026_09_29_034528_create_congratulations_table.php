<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historique des félicitations reçues : un envoi du navigateur (clics
     * regroupés) par ligne, avec son motif, pour savoir pourquoi on félicite.
     */
    public function up(): void
    {
        Schema::create('congratulations', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->foreignId('celebration_id')->nullable()->constrained()->nullOnDelete();
            // Motif figé au moment du clic : reste lisible si la surprise est modifiée ou supprimée.
            $table->string('reason', 300);
            $table->unsignedSmallInteger('count');
            // Inconnue pour les félicitations antérieures à cet historique.
            $table->string('locale', 5)->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('congratulations');
    }
};

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
        Schema::create('distinctions', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->string('issuer');
            $table->json('description')->nullable();
            $table->date('awarded_on');
            $table->string('url')->nullable();
            // Contexte facultatif : une expérience, une formation ou un projet.
            $table->nullableMorphs('distinguishable');
            $table->unsignedInteger('congratulations_count')->default(0);
            $table->string('status')->default('published')->index();
            $table->string('drafted_by', 60)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distinctions');
    }
};

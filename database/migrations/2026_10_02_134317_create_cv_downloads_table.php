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
        Schema::create('cv_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('locale', 2);
            $table->string('source');
            $table->string('email')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('referrer_host')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('device', 16)->nullable();
            // Empreinte de l'IP renouvelée chaque jour : sert à ignorer les doubles clics, jamais l'IP en clair.
            $table->string('visitor_hash', 64)->index();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cv_downloads');
    }
};

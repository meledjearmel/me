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
        Schema::create('engagements', function (Blueprint $table) {
            $table->id();
            // « freelance » (projet à confier) ou « hiring » (recrutement).
            $table->string('type');
            $table->string('name');
            $table->string('email');
            $table->string('company')->nullable();
            // Freelance : type de projet ; recrutement : intitulé du poste.
            $table->string('subject')->nullable();
            $table->foreignId('job_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contract')->nullable();
            // Budget : « fixed » (montant forfaitaire) ou « period » (tarif par heure, jour, semaine, mois ou an).
            $table->string('budget_type')->nullable();
            $table->unsignedBigInteger('budget_amount')->nullable();
            $table->string('budget_currency', 3)->nullable();
            $table->string('budget_period')->nullable();
            $table->string('timeline')->nullable();
            $table->text('message')->nullable();
            $table->string('locale', 5)->default('fr');
            $table->string('status')->default('new');
            // Recrutement : date d'envoi du CV par email.
            $table->timestamp('cv_sent_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('engagements');
    }
};

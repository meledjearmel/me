<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les pages de candidature : une page privée par entreprise (adresse difficile à deviner,
     * jamais indexée), avec un mot d'accompagnement, une sélection de projets et le CV du
     * profil visé ; et ce qui s'y passe (consultations, téléchargements du CV).
     */
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            // Partie publique de l'adresse : nom de l'entreprise et suffixe aléatoire.
            $table->string('token', 120)->unique();
            $table->string('company');
            $table->string('contact_name')->nullable();
            $table->string('position');
            $table->foreignId('job_profile_id')->nullable()->constrained()->nullOnDelete();
            // La langue de la page (et du CV proposé).
            $table->string('locale', 2)->default('fr');
            $table->text('intro');
            $table->string('job_posting_url', 2000)->nullable();
            // Le texte de l'offre, gardé pour l'IA et pour s'y référer.
            $table->text('job_posting_text')->nullable();
            // Notes privées, jamais affichées sur la page.
            $table->text('notes')->nullable();
            // draft (pas encore en ligne), active ou archived (lien coupé).
            $table->string('status')->default('draft');
            $table->string('drafted_by', 60)->nullable();
            $table->date('expires_on')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('cv_downloads_count')->default(0);
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_application_project', function (Blueprint $table) {
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->primary(['job_application_id', 'project_id']);
        });

        Schema::create('job_application_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            // view (consultation, une par visiteur et par jour) ou cv_download.
            $table->string('type');
            $table->string('country_code', 2)->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('device', 20)->nullable();
            $table->string('referrer_host')->nullable();
            $table->string('visitor_hash', 64)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['job_application_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_application_events');
        Schema::dropIfExists('job_application_project');
        Schema::dropIfExists('job_applications');
    }
};

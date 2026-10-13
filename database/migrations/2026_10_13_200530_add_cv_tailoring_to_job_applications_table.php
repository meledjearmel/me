<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le CV adapté à une candidature : titre et résumé réécrits pour l'offre, réalisations
     * choisies et ordonnées parmi celles du site, compétences mises en avant. Sans adaptation,
     * la page propose le CV standard du profil.
     */
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->json('cv_tailoring')->nullable()->after('job_profile_id');
            $table->timestamp('cv_tailored_at')->nullable()->after('cv_tailoring');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn(['cv_tailoring', 'cv_tailored_at']);
        });
    }
};

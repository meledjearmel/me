<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Réglages déplacés du profil vers les réglages du site. */
    private const array COLUMNS = ['testimonial_video_enabled', 'cv_job_profile_id', 'cv_source', 'congratulation_notify_minutes'];

    /**
     * Le profil ne garde que ce qui me décrit : les réglages de gestion du site
     * (avis vidéo, CV, notifications) passent dans site_settings, valeurs comprises.
     */
    public function up(): void
    {
        $profile = DB::table('profiles')->whereNull('deleted_at')->orderBy('id')->first(self::COLUMNS);

        // Sans profil (base neuve), rien à reprendre : la ligne sera créée au premier accès.
        if ($profile !== null) {
            if (DB::table('site_settings')->exists()) {
                DB::table('site_settings')->update((array) $profile);
            } else {
                DB::table('site_settings')->insert([...(array) $profile, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cv_job_profile_id');
            $table->dropColumn(['cv_source', 'congratulation_notify_minutes', 'testimonial_video_enabled']);
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->foreignId('cv_job_profile_id')->nullable()->after('social_links')->constrained('job_profiles')->nullOnDelete();
            $table->string('cv_source')->default('uploaded')->after('cv_job_profile_id');
            $table->boolean('testimonial_video_enabled')->default(true)->after('cv_source');
            $table->unsignedSmallInteger('congratulation_notify_minutes')->default(10);
        });

        $settings = DB::table('site_settings')->orderBy('id')->first(self::COLUMNS);

        if ($settings) {
            DB::table('profiles')->update((array) $settings);
        }
    }
};

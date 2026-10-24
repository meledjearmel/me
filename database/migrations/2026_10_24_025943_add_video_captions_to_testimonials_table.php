<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sous-titres de la vidéo d'un avis, au format WebVTT, par langue.
     */
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->json('video_captions')->nullable()->after('video_transcript');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn('video_captions');
        });
    }
};

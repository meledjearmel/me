<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phrase d'accroche (affichée en grand sur les cartes) et transcription de
     * l'avis vidéo, toutes deux traduisibles. La vidéo elle-même est un média.
     */
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->json('highlight')->nullable()->after('content');
            $table->json('video_transcript')->nullable()->after('highlight');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn(['highlight', 'video_transcript']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les visiteurs peuvent-ils joindre (ou filmer) une vidéo à leur avis ?
     */
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->boolean('testimonial_video_enabled')->default(true)->after('cv_source');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('testimonial_video_enabled');
        });
    }
};

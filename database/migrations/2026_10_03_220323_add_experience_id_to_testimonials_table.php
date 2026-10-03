<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * L'expérience (poste, entreprise) ou la formation à laquelle se rapporte un avis, comme project_id pour un projet.
     */
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->foreignId('experience_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
            $table->foreignId('education_id')->nullable()->after('experience_id')->constrained('educations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('education_id');
            $table->dropConstrainedForeignId('experience_id');
        });
    }
};

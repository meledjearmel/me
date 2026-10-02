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
        Schema::table('profiles', function (Blueprint $table) {
            $table->foreignId('cv_job_profile_id')->nullable()->after('social_links')->constrained('job_profiles')->nullOnDelete();
            $table->string('cv_source')->default('uploaded')->after('cv_job_profile_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cv_job_profile_id');
            $table->dropColumn('cv_source');
        });
    }
};

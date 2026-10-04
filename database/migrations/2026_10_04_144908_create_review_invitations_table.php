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
        Schema::create('review_invitations', function (Blueprint $table) {
            $table->id();
            // Clé du lien personnel envoyé à la personne invitée à laisser un avis.
            $table->string('token', 64)->unique();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('locale', 5)->default('fr');
            // Rattachement facultatif : l'avis peut aussi être général.
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('experience_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('education_id')->nullable()->constrained('educations')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('testimonial_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_invitations');
    }
};

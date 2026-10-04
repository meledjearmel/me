<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('author_name', 80);
            // Jamais affiché : seulement pour pouvoir répondre en privé.
            $table->string('author_email')->nullable();
            $table->text('body');
            // Langue de la page où le commentaire a été écrit.
            $table->string('locale', 2);
            $table->string('status', 16)->default('pending')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_comments');
    }
};

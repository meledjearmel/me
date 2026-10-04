<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16);
            // Empreinte du cookie anonyme du lecteur : une réaction de chaque type par lecteur.
            $table->string('reader_hash', 64);
            $table->timestamps();

            $table->unique(['post_id', 'type', 'reader_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_reactions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            // Réseau ou moyen de partage : linkedin, x, whatsapp, facebook, email, copy, native.
            $table->string('network', 16);
            $table->timestamps();

            $table->index(['post_id', 'network']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_shares');
    }
};

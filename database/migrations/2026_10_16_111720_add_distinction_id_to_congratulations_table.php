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
        Schema::table('congratulations', function (Blueprint $table) {
            // La distinction félicitée (carte « Distinction » de la page À propos).
            $table->foreignId('distinction_id')->nullable()->after('celebration_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('congratulations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('distinction_id');
        });
    }
};

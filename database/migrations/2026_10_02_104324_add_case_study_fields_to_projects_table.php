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
        Schema::table('projects', function (Blueprint $table) {
            $table->json('tagline')->nullable()->after('slug');
            $table->json('role')->nullable()->after('tagline');
            $table->json('client')->nullable()->after('role');
            $table->json('platform')->nullable()->after('client');
            $table->json('key_figures')->nullable()->after('result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['tagline', 'role', 'client', 'platform', 'key_figures']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Sous les articles du blog : réactions anonymes (emojis) et commentaires modérés.
            $table->boolean('blog_reactions_enabled')->default(true)->after('blog_enabled');
            $table->boolean('blog_comments_enabled')->default(true)->after('blog_reactions_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['blog_reactions_enabled', 'blog_comments_enabled']);
        });
    }
};

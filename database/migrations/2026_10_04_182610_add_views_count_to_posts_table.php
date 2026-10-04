<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Lectures affichées sur le site : une par session toutes les 30 minutes, robots exclus.
            $table->unsignedInteger('views_count')->default(0)->after('reading_minutes');
        });

        // Reprend les visites déjà enregistrées des pages d'article (`fr/blog/<slug>`, `en/blog/<slug>`).
        DB::table('page_visits')
            ->where('path', 'like', '%/blog/%')
            ->pluck('path')
            ->map(fn (string $path): ?string => preg_match('~^[a-z]{2}/blog/([^/]+)$~', ltrim($path, '/'), $matches) && $matches[1] !== 'feed' ? $matches[1] : null)
            ->filter()
            ->countBy()
            ->each(fn (int $views, string $slug) => DB::table('posts')->where('slug', $slug)->update(['views_count' => $views]));
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('views_count');
        });
    }
};

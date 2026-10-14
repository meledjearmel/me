<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La republication des articles : sur dev.to et Hashnode (par leur API, avec un lien
     * canonique vers le site), et les textes du kit de partage pour LinkedIn, X, Facebook et
     * WhatsApp, rédigés par l'IA.
     */
    public function up(): void
    {
        Schema::create('post_syndications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            // devto ou hashnode.
            $table->string('platform', 20);
            // pending, published ou failed.
            $table->string('status', 20)->default('pending');
            $table->string('external_id')->nullable();
            $table->string('external_url', 2000)->nullable();
            $table->string('locale', 2)->default('en');
            $table->text('error')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['post_id', 'platform']);
        });

        Schema::table('posts', function (Blueprint $table) {
            // {"fr": {"linkedin": "…", "x": "…", "facebook": "…", "whatsapp": "…"}, "en": {…}}
            $table->json('social_texts')->nullable();
        });

        Schema::table('site_settings', function (Blueprint $table) {
            foreach (['devto', 'hashnode'] as $platform) {
                // off, manual (bouton sur la fiche) ou auto (chaque article publié part tout seul).
                $table->string("{$platform}_mode", 10)->default('off');
                $table->text("{$platform}_api_key")->nullable();
                // La langue republiée (en par défaut : l'audience de ces plateformes).
                $table->string("{$platform}_locale", 2)->default('en');
                // En mode auto, seuls les articles publiés après l'activation partent.
                $table->timestamp("{$platform}_auto_since")->nullable();
            }

            $table->string('hashnode_publication_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'devto_mode', 'devto_api_key', 'devto_locale', 'devto_auto_since',
                'hashnode_mode', 'hashnode_api_key', 'hashnode_locale', 'hashnode_auto_since', 'hashnode_publication_id',
            ]);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('social_texts');
        });

        Schema::dropIfExists('post_syndications');
    }
};

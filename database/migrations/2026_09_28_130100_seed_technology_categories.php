<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Crée les 9 catégories qui existaient jusqu'ici comme un enum figé (App\Enums\TechnologyCategory,
     * supprimé par cette conversion). Elles deviennent une donnée éditable depuis l'admin : cette
     * migration ne fait que poser une valeur de départ, jamais réécrite si la catégorie existe déjà
     * (`key` unique), donc jamais un renommage fait depuis l'admin. La migration suivante a besoin que
     * ces lignes existent déjà pour convertir `technologies.category` en `technologies.category_id`.
     */
    public function up(): void
    {
        $categories = [
            ['key' => 'langages', 'label' => ['fr' => 'Langages', 'en' => 'Languages'], 'sort_order' => 1],
            ['key' => 'frameworks', 'label' => ['fr' => 'Frameworks', 'en' => 'Frameworks'], 'sort_order' => 2],
            ['key' => 'donnees', 'label' => ['fr' => 'Données', 'en' => 'Data'], 'sort_order' => 3],
            ['key' => 'qualite', 'label' => ['fr' => 'Qualité', 'en' => 'Quality'], 'sort_order' => 4],
            ['key' => 'securite', 'label' => ['fr' => 'Sécurité', 'en' => 'Security'], 'sort_order' => 5],
            ['key' => 'infra', 'label' => ['fr' => 'Infra', 'en' => 'Infra'], 'sort_order' => 6],
            ['key' => 'ia', 'label' => ['fr' => 'IA', 'en' => 'AI'], 'sort_order' => 7],
            ['key' => 'design', 'label' => ['fr' => 'Design', 'en' => 'Design'], 'sort_order' => 8],
            ['key' => 'cms', 'label' => ['fr' => 'CMS', 'en' => 'CMS'], 'sort_order' => 9],
        ];

        $now = now();

        foreach ($categories as $category) {
            DB::table('technology_categories')->insertOrIgnore([
                'key' => $category['key'],
                'label' => json_encode($category['label'], JSON_UNESCAPED_UNICODE),
                'sort_order' => $category['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Rien à défaire : supprimer ces lignes perdrait aussi tout renommage fait depuis
     * l'admin, ce que cette migration n'a justement jamais le droit de faire.
     */
    public function down(): void {}
};

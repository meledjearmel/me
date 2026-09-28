<?php

namespace Database\Seeders;

use App\Models\TechnologyCategory;
use Illuminate\Database\Seeder;

class TechnologyCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * En pratique déjà posées par la migration 2026_09_28_130100_seed_technology_categories
     * (nécessaire pour convertir les technologies existantes) : ce seeder ne sert qu'à recréer
     * les catégories par défaut sur une base qui les aurait perdues (reset local, par exemple).
     * Comme TechnologySeeder, il ne complète que ce qui manque et n'écrase jamais une catégorie
     * déjà là, pour ne jamais effacer un renommage fait depuis l'admin.
     */
    public function run(): void
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

        foreach ($categories as $category) {
            TechnologyCategory::query()->firstOrCreate(['key' => $category['key']], $category);
        }
    }
}

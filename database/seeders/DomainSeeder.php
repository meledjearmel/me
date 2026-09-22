<?php

namespace Database\Seeders;

use App\Models\Domain;
use Illuminate\Database\Seeder;

class DomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $domains = [
            [
                'key' => 'dev',
                'label' => ['fr' => 'Développement', 'en' => 'Development'],
                'color' => '#6366F1',
                'icon' => 'code',
                'sort_order' => 1,
            ],
            [
                'key' => 'infra',
                'label' => ['fr' => 'Infra & IT', 'en' => 'Infra & IT'],
                'color' => '#34D399',
                'icon' => 'server',
                'sort_order' => 2,
            ],
            [
                'key' => 'securite',
                'label' => ['fr' => 'Sécurité', 'en' => 'Security'],
                'color' => '#F59E0B',
                'icon' => 'shield',
                'sort_order' => 3,
            ],
            [
                'key' => 'design',
                'label' => ['fr' => 'Design', 'en' => 'Design'],
                'color' => '#F472B6',
                'icon' => 'palette',
                'sort_order' => 4,
            ],
        ];

        foreach ($domains as $domain) {
            Domain::query()->updateOrCreate(['key' => $domain['key']], $domain);
        }
    }
}

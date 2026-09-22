<?php

namespace Database\Seeders;

use App\Enums\TechnologyCategory;
use App\Models\Technology;
use Illuminate\Database\Seeder;

class TechnologySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $technologies = [
            ['name' => 'PHP', 'category' => TechnologyCategory::Langages, 'icon' => 'php'],
            ['name' => 'TypeScript', 'category' => TechnologyCategory::Langages, 'icon' => 'typescript'],
            ['name' => 'Laravel', 'category' => TechnologyCategory::Frameworks, 'icon' => 'laravel'],
            ['name' => 'Inertia.js', 'category' => TechnologyCategory::Frameworks, 'icon' => 'inertia'],
            ['name' => 'React', 'category' => TechnologyCategory::Frameworks, 'icon' => 'react'],
            ['name' => 'PostgreSQL', 'category' => TechnologyCategory::Donnees, 'icon' => 'postgresql'],
            ['name' => 'MySQL', 'category' => TechnologyCategory::Donnees, 'icon' => 'mysql'],
            ['name' => 'Redis', 'category' => TechnologyCategory::Donnees, 'icon' => 'redis'],
            ['name' => 'Pest', 'category' => TechnologyCategory::Qualite, 'icon' => 'pest'],
            ['name' => 'PHPStan', 'category' => TechnologyCategory::Qualite, 'icon' => 'phpstan'],
            ['name' => 'Fortify (2FA)', 'category' => TechnologyCategory::Securite, 'icon' => 'shield'],
            ['name' => 'Proxmox', 'category' => TechnologyCategory::Infra, 'icon' => 'proxmox'],
            ['name' => 'Docker', 'category' => TechnologyCategory::Infra, 'icon' => 'docker'],
            ['name' => 'Nginx Proxy Manager', 'category' => TechnologyCategory::Infra, 'icon' => 'nginx'],
            ['name' => 'Ollama', 'category' => TechnologyCategory::Ia, 'icon' => 'ollama'],
        ];

        foreach ($technologies as $technology) {
            Technology::query()->updateOrCreate(['name' => $technology['name']], $technology);
        }
    }
}

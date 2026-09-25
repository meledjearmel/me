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
            ['name' => 'Inertia', 'category' => TechnologyCategory::Frameworks, 'icon' => 'inertia'],
            ['name' => 'React', 'category' => TechnologyCategory::Frameworks, 'icon' => 'react'],
            ['name' => 'PostgreSQL', 'category' => TechnologyCategory::Donnees, 'icon' => 'postgresql'],
            ['name' => 'MySQL', 'category' => TechnologyCategory::Donnees, 'icon' => 'mysql'],
            ['name' => 'Redis', 'category' => TechnologyCategory::Donnees, 'icon' => 'redis'],
            ['name' => 'Pest', 'category' => TechnologyCategory::Qualite, 'icon' => 'pest'],
            ['name' => 'PHPStan', 'category' => TechnologyCategory::Qualite, 'icon' => 'phpstan'],
            ['name' => 'Fortify (2FA)', 'category' => TechnologyCategory::Securite, 'icon' => 'shield'],
            ['name' => 'Proxmox', 'category' => TechnologyCategory::Infra, 'icon' => 'proxmox'],
            ['name' => 'Docker', 'category' => TechnologyCategory::Infra, 'icon' => 'docker'],
            ['name' => 'Nginx', 'category' => TechnologyCategory::Infra, 'icon' => 'nginx'],
            ['name' => 'Ollama', 'category' => TechnologyCategory::Ia, 'icon' => 'ollama'],
            ['name' => 'Vite', 'category' => TechnologyCategory::Frameworks, 'icon' => 'vite'],
            ['name' => 'TailwindCSS', 'category' => TechnologyCategory::Frameworks, 'icon' => 'tailwindcss'],
            ['name' => 'Alpine.js', 'category' => TechnologyCategory::Frameworks, 'icon' => 'alpinejs'],
            ['name' => 'Livewire', 'category' => TechnologyCategory::Frameworks, 'icon' => 'livewire'],
            ['name' => 'Expo', 'category' => TechnologyCategory::Frameworks, 'icon' => 'expo'],
            ['name' => 'JS Vanilla', 'category' => TechnologyCategory::Langages, 'icon' => 'javascript'],
            ['name' => 'Node.js', 'category' => TechnologyCategory::Frameworks, 'icon' => 'nodejs'],
            ['name' => 'Figma', 'category' => TechnologyCategory::Design, 'icon' => 'figma'],
            ['name' => 'Anthropic AI', 'category' => TechnologyCategory::Ia, 'icon' => 'anthropic'],
            ['name' => 'OpenAI', 'category' => TechnologyCategory::Ia, 'icon' => 'openai'],
            ['name' => 'WordPress', 'category' => TechnologyCategory::Cms, 'icon' => 'wordpress'],
            ['name' => 'Elementor', 'category' => TechnologyCategory::Cms, 'icon' => 'elementor'],
            ['name' => 'Divi', 'category' => TechnologyCategory::Cms, 'icon' => 'divi'],
            ['name' => 'Alphabet', 'category' => TechnologyCategory::Ia, 'icon' => 'google'],
            ['name' => 'PhpStorm', 'category' => TechnologyCategory::Qualite, 'icon' => 'phpstorm'],
            ['name' => 'Cursor', 'category' => TechnologyCategory::Ia, 'icon' => 'cursor'],
            ['name' => 'Ubuntu', 'category' => TechnologyCategory::Infra, 'icon' => 'ubuntu'],
            ['name' => 'Debian', 'category' => TechnologyCategory::Infra, 'icon' => 'debian'],
            ['name' => 'Apache', 'category' => TechnologyCategory::Infra, 'icon' => 'apache'],
            ['name' => 'MikroTik', 'category' => TechnologyCategory::Infra, 'icon' => 'mikrotik'],
            ['name' => 'WireGuard', 'category' => TechnologyCategory::Infra, 'icon' => 'wireguard'],
            ['name' => 'Tailscale', 'category' => TechnologyCategory::Infra, 'icon' => 'tailscale'],
            ['name' => 'PHPUnit', 'category' => TechnologyCategory::Qualite, 'icon' => 'phpunit'],
            ['name' => 'Git', 'category' => TechnologyCategory::Qualite, 'icon' => 'git'],
            ['name' => 'GitHub', 'category' => TechnologyCategory::Qualite, 'icon' => 'github'],
            ['name' => 'GitLab', 'category' => TechnologyCategory::Qualite, 'icon' => 'gitlab'],
            ['name' => 'Flutter', 'category' => TechnologyCategory::Frameworks, 'icon' => 'flutter'],
            ['name' => 'React Native', 'category' => TechnologyCategory::Frameworks, 'icon' => 'react'],
            ['name' => 'Vue.js', 'category' => TechnologyCategory::Frameworks, 'icon' => 'vuejs'],
            ['name' => 'Linux', 'category' => TechnologyCategory::Infra, 'icon' => 'linux'],
            ['name' => 'Zabbix', 'category' => TechnologyCategory::Infra, 'icon' => 'zabbix'],
            ['name' => 'GLPI', 'category' => TechnologyCategory::Infra, 'icon' => 'glpi'],
            ['name' => 'MariaDB', 'category' => TechnologyCategory::Donnees, 'icon' => 'mariadb'],
            ['name' => 'SQLite', 'category' => TechnologyCategory::Donnees, 'icon' => 'sqlite'],
            ['name' => 'Vitest', 'category' => TechnologyCategory::Qualite, 'icon' => 'vitest'],
            ['name' => 'Angular', 'category' => TechnologyCategory::Frameworks, 'icon' => 'angular'],
            ['name' => 'NativePHP', 'category' => TechnologyCategory::Frameworks, 'icon' => 'nativephp'],
        ];

        Technology::query()->withTrashed()->whereIn('name', ['Nginx Proxy Manager', 'Claude Code'])->forceDelete();

        foreach ($technologies as $technology) {
            Technology::query()->updateOrCreate(['name' => $technology['name']], $technology);
        }
    }
}

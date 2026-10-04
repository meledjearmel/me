<?php

namespace Database\Seeders;

use App\Enums\UsesCategory;
use App\Models\Technology;
use App\Models\UsesItem;
use Illuminate\Database\Seeder;

/**
 * Contenu de la page « Uses », à compléter et corriger depuis l'administration : mes
 * outils décrits à la main, puis les technologies du site, rangées dans les rubriques.
 *
 * N'ajoute que les éléments absents (par nom) : un re-seed n'écrase jamais une
 * modification et ne recrée pas un élément supprimé (il reste dans la corbeille).
 * Seule exception : il complète le lien d'une technologie quand il est vide.
 */
class UsesItemSeeder extends Seeder
{
    /**
     * Rubrique « Uses » de chaque catégorie de technologie.
     *
     * @var array<string, UsesCategory>
     */
    private const array TECHNOLOGY_CATEGORIES = [
        'langages' => UsesCategory::Development,
        'frameworks' => UsesCategory::Development,
        'donnees' => UsesCategory::Development,
        'qualite' => UsesCategory::Development,
        'securite' => UsesCategory::Development,
        'cms' => UsesCategory::Development,
        'design' => UsesCategory::Apps,
        'infra' => UsesCategory::Services,
        'ia' => UsesCategory::Services,
    ];

    /**
     * Technologies rangées ailleurs que leur catégorie ne l'indique.
     *
     * @var array<string, UsesCategory>
     */
    private const array TECHNOLOGY_EXCEPTIONS = [
        'Cursor' => UsesCategory::Development,
        'Ollama' => UsesCategory::Development,
        'MikroTik' => UsesCategory::Hardware,
    ];

    /** Les technologies passent après les outils décrits à la main dans chaque rubrique. */
    private const int TECHNOLOGY_SORT_OFFSET = 100;

    /**
     * Site officiel de chaque technologie (le modèle Technology n'a pas de lien).
     *
     * @var array<string, string>
     */
    private const array TECHNOLOGY_URLS = [
        'PHP' => 'https://www.php.net/',
        'TypeScript' => 'https://www.typescriptlang.org/',
        'Laravel' => 'https://laravel.com/',
        'Inertia' => 'https://inertiajs.com/',
        'React' => 'https://react.dev/',
        'PostgreSQL' => 'https://www.postgresql.org/',
        'MySQL' => 'https://www.mysql.com/',
        'Redis' => 'https://redis.io/',
        'Pest' => 'https://pestphp.com/',
        'PHPStan' => 'https://phpstan.org/',
        'Fortify (2FA)' => 'https://laravel.com/docs/fortify',
        'Proxmox' => 'https://www.proxmox.com/',
        'Docker' => 'https://www.docker.com/',
        'Nginx' => 'https://nginx.org/',
        'Ollama' => 'https://ollama.com/',
        'Vite' => 'https://vite.dev/',
        'TailwindCSS' => 'https://tailwindcss.com/',
        'Alpine.js' => 'https://alpinejs.dev/',
        'Livewire' => 'https://livewire.laravel.com/',
        'Expo' => 'https://expo.dev/',
        'JS Vanilla' => 'https://developer.mozilla.org/docs/Web/JavaScript',
        'Node.js' => 'https://nodejs.org/',
        'Figma' => 'https://www.figma.com/',
        'Anthropic AI' => 'https://www.anthropic.com/',
        'OpenAI' => 'https://openai.com/',
        'WordPress' => 'https://wordpress.org/',
        'Elementor' => 'https://elementor.com/',
        'Divi' => 'https://www.elegantthemes.com/gallery/divi/',
        'Alphabet' => 'https://abc.xyz/',
        'PhpStorm' => 'https://www.jetbrains.com/phpstorm/',
        'Cursor' => 'https://cursor.com/',
        'Ubuntu' => 'https://ubuntu.com/',
        'Debian' => 'https://www.debian.org/',
        'Apache' => 'https://httpd.apache.org/',
        'MikroTik' => 'https://mikrotik.com/',
        'WireGuard' => 'https://www.wireguard.com/',
        'Tailscale' => 'https://tailscale.com/',
        'PHPUnit' => 'https://phpunit.de/',
        'Git' => 'https://git-scm.com/',
        'GitHub' => 'https://github.com/',
        'GitLab' => 'https://about.gitlab.com/',
        'Flutter' => 'https://flutter.dev/',
        'Dart' => 'https://dart.dev/',
        'React Native' => 'https://reactnative.dev/',
        'Vue.js' => 'https://vuejs.org/',
        'Linux' => 'https://www.kernel.org/',
        'Zabbix' => 'https://www.zabbix.com/',
        'GLPI' => 'https://glpi-project.org/',
        'MariaDB' => 'https://mariadb.org/',
        'SQLite' => 'https://www.sqlite.org/',
        'Vitest' => 'https://vitest.dev/',
        'Angular' => 'https://angular.dev/',
        'NativePHP' => 'https://nativephp.com/',
    ];

    public function run(): void
    {
        $existing = UsesItem::withTrashed()->pluck('name')->map(fn (string $name): string => mb_strtolower($name))->flip();

        $add = function (array $attributes) use ($existing): void {
            if (! $existing->has(mb_strtolower($attributes['name']))) {
                UsesItem::query()->create($attributes);
                $existing->put(mb_strtolower($attributes['name']), true);
            }
        };

        foreach ($this->items() as $category => $items) {
            foreach ($items as $order => [$name, $url, $fr, $en]) {
                $add([
                    'category' => UsesCategory::from($category),
                    'name' => $name,
                    'url' => $url,
                    'description' => ['fr' => $fr, 'en' => $en],
                    'sort_order' => $order,
                ]);
            }
        }

        $technologies = Technology::query()->with('category')->orderBy('name')->get();

        foreach ($technologies->values() as $order => $technology) {
            $category = self::TECHNOLOGY_EXCEPTIONS[$technology->name]
                ?? self::TECHNOLOGY_CATEGORIES[$technology->category?->key ?? '']
                ?? UsesCategory::Development;

            $add([
                'category' => $category,
                'name' => $technology->name,
                'url' => self::TECHNOLOGY_URLS[$technology->name] ?? null,
                'description' => $technology->getTranslations('description') ?: null,
                'sort_order' => self::TECHNOLOGY_SORT_OFFSET + $order,
            ]);
        }

        // Éléments créés avant que les liens existent : on complète un lien vide, sans
        // jamais remplacer un lien saisi dans l'administration.
        foreach (self::TECHNOLOGY_URLS as $name => $url) {
            UsesItem::withTrashed()->where('name', $name)->whereNull('url')->update(['url' => $url]);
        }
    }

    /**
     * @return array<string, list<array{0: string, 1: string|null, 2: string, 3: string}>>
     */
    private function items(): array
    {
        return [
            'hardware' => [
                ['PC sous Windows 11', null, 'Ma machine de développement au quotidien, avec Laravel Herd pour servir les projets en local.', 'My everyday development machine, with Laravel Herd serving projects locally.'],
                ['Serveurs Linux (Ubuntu, Debian)', null, 'Là où tournent mes applications en production, derrière Nginx.', 'Where my applications run in production, behind Nginx.'],
            ],
            'development' => [
                ['PhpStorm', 'https://www.jetbrains.com/phpstorm/', 'Mon éditeur principal pour PHP et Laravel : refactorisation, débogage et navigation dans le code.', 'My main editor for PHP and Laravel: refactoring, debugging and code navigation.'],
                ['Visual Studio Code', 'https://code.visualstudio.com/', 'Pour le front React et TypeScript, et les modifications rapides.', 'For the React and TypeScript front end, and quick edits.'],
                ['Laravel Herd', 'https://herd.laravel.com/', 'PHP, sites locaux et services sans configuration.', 'PHP, local sites and services with zero configuration.'],
                ['Pest', 'https://pestphp.com/', 'Les tests de chaque fonctionnalité, lancés avant chaque livraison.', 'Tests for every feature, run before each release.'],
                ['Bun', 'https://bun.sh/', 'Installation des dépendances JavaScript et build du front.', 'JavaScript dependency installs and front-end builds.'],
                ['Expo', 'https://expo.dev/', 'Pour mes applications mobiles en React Native.', 'For my React Native mobile apps.'],
                ['Flutter', 'https://flutter.dev/', 'Pour les applications mobiles multiplateformes.', 'For cross-platform mobile apps.'],
                ['Docker', 'https://www.docker.com/', 'Des environnements identiques du poste de développement au serveur.', 'Identical environments from my workstation to the server.'],
            ],
            'apps' => [
                ['Figma', 'https://www.figma.com/', 'Maquettes et prototypes avant d’écrire le code.', 'Mockups and prototypes before writing code.'],
                ['Notion', 'https://www.notion.so/', 'Notes, documentation de projets et suivi des tâches.', 'Notes, project documentation and task tracking.'],
                ['Canva', 'https://www.canva.com/', 'Visuels rapides : images de partage, présentations.', 'Quick visuals: social images, presentations.'],
                ['Google Workspace', 'https://workspace.google.com/', 'Email, agenda et documents partagés.', 'Email, calendar and shared documents.'],
            ],
            'services' => [
                ['GitHub', 'https://github.com/', 'Hébergement du code, revues et historique de chaque projet.', 'Code hosting, reviews and the history of every project.'],
                ['Tailscale', 'https://tailscale.com/', 'Accès sécurisé à mes serveurs, où que je sois.', 'Secure access to my servers, wherever I am.'],
                ['Groq', 'https://groq.com/', 'Les modèles d’IA rapides derrière l’assistant de ce site et l’aide à la rédaction.', 'The fast AI models behind this site’s assistant and writing help.'],
                ['Jitsi Meet', 'https://meet.jit.si/', 'Les visioconférences des rendez-vous pris sur ce site.', 'Video calls for the meetings booked on this site.'],
            ],
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\Technology;
use App\Models\TechnologyCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class TechnologySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Descriptions bilingues : affichées en infobulle au survol du logo sur la page publique.
        $technologies = [
            ['name' => 'PHP', 'category' => 'langages', 'icon' => 'php', 'description' => ['fr' => 'Langage serveur au cœur de mes applications web.', 'en' => 'Server-side language at the core of my web apps.']],
            ['name' => 'TypeScript', 'category' => 'langages', 'icon' => 'typescript', 'description' => ['fr' => 'JavaScript typé : moins de bugs, un code plus sûr.', 'en' => 'Typed JavaScript: fewer bugs, safer code.']],
            ['name' => 'Laravel', 'category' => 'frameworks', 'icon' => 'laravel', 'description' => ['fr' => 'Framework PHP pour bâtir des applications web solides.', 'en' => 'PHP framework for building solid web applications.']],
            ['name' => 'Inertia', 'category' => 'frameworks', 'icon' => 'inertia', 'description' => ['fr' => 'Relie Laravel et React sans API à maintenir.', 'en' => 'Connects Laravel and React without maintaining an API.']],
            ['name' => 'React', 'category' => 'frameworks', 'icon' => 'react', 'description' => ['fr' => 'Bibliothèque pour construire des interfaces interactives.', 'en' => 'Library for building interactive user interfaces.']],
            ['name' => 'PostgreSQL', 'category' => 'donnees', 'icon' => 'postgresql', 'description' => ['fr' => 'Base de données relationnelle robuste et fiable.', 'en' => 'Robust and reliable relational database.']],
            ['name' => 'MySQL', 'category' => 'donnees', 'icon' => 'mysql', 'description' => ['fr' => 'Base de données relationnelle très répandue.', 'en' => 'Widely used relational database.']],
            ['name' => 'Redis', 'category' => 'donnees', 'icon' => 'redis', 'description' => ['fr' => "Base en mémoire : cache, files d'attente, sessions.", 'en' => 'In-memory store: cache, queues, sessions.']],
            ['name' => 'Pest', 'category' => 'qualite', 'icon' => 'pest', 'description' => ['fr' => 'Framework de tests PHP simple et lisible.', 'en' => 'Simple, readable PHP testing framework.']],
            ['name' => 'PHPStan', 'category' => 'qualite', 'icon' => 'phpstan', 'description' => ['fr' => "Analyse statique qui repère les erreurs avant l'exécution.", 'en' => 'Static analysis that catches errors before runtime.']],
            ['name' => 'Fortify (2FA)', 'category' => 'securite', 'icon' => 'shield', 'description' => ['fr' => 'Authentification à deux facteurs pour Laravel.', 'en' => 'Two-factor authentication for Laravel.']],
            ['name' => 'Proxmox', 'category' => 'infra', 'icon' => 'proxmox', 'description' => ['fr' => 'Plateforme de virtualisation pour héberger mes serveurs.', 'en' => 'Virtualization platform to host my servers.']],
            ['name' => 'Docker', 'category' => 'infra', 'icon' => 'docker', 'description' => ['fr' => 'Conteneurs pour des environnements reproductibles.', 'en' => 'Containers for reproducible environments.']],
            ['name' => 'Nginx', 'category' => 'infra', 'icon' => 'nginx', 'description' => ['fr' => 'Serveur web et reverse proxy performant.', 'en' => 'High-performance web server and reverse proxy.']],
            ['name' => 'Ollama', 'category' => 'ia', 'icon' => 'ollama', 'description' => ['fr' => "Fait tourner des modèles d'IA en local.", 'en' => 'Runs AI models locally.']],
            ['name' => 'Vite', 'category' => 'frameworks', 'icon' => 'vite', 'description' => ['fr' => 'Outil de build front-end ultra rapide.', 'en' => 'Blazing-fast front-end build tool.']],
            ['name' => 'TailwindCSS', 'category' => 'frameworks', 'icon' => 'tailwindcss', 'description' => ['fr' => 'Framework CSS utilitaire pour styliser vite.', 'en' => 'Utility-first CSS framework for styling fast.']],
            ['name' => 'Alpine.js', 'category' => 'frameworks', 'icon' => 'alpinejs', 'description' => ['fr' => "Un peu d'interactivité légère directement dans le HTML.", 'en' => 'Lightweight interactivity right in the HTML.']],
            ['name' => 'Livewire', 'category' => 'frameworks', 'icon' => 'livewire', 'description' => ['fr' => 'Interfaces dynamiques en Laravel, sans écrire de JS.', 'en' => 'Dynamic Laravel interfaces without writing JS.']],
            ['name' => 'Expo', 'category' => 'frameworks', 'icon' => 'expo', 'description' => ['fr' => 'Plateforme pour créer des applications mobiles React Native.', 'en' => 'Platform for building React Native mobile apps.']],
            ['name' => 'JS Vanilla', 'category' => 'langages', 'icon' => 'javascript', 'description' => ['fr' => 'Le langage du navigateur, utilisé sans framework.', 'en' => 'The language of the browser, used without a framework.']],
            ['name' => 'Node.js', 'category' => 'frameworks', 'icon' => 'nodejs', 'description' => ['fr' => 'Environnement pour exécuter JavaScript côté serveur.', 'en' => 'Runtime to execute JavaScript on the server.']],
            ['name' => 'Figma', 'category' => 'design', 'icon' => 'figma', 'description' => ['fr' => "Outil de design d'interfaces et de prototypage collaboratif.", 'en' => 'Collaborative interface design and prototyping tool.']],
            ['name' => 'Anthropic AI', 'category' => 'ia', 'icon' => 'anthropic', 'description' => ['fr' => "Assistant IA d'Anthropic (Claude) pour coder et rédiger.", 'en' => "Anthropic's AI assistant (Claude) for coding and writing."]],
            ['name' => 'OpenAI', 'category' => 'ia', 'icon' => 'openai', 'description' => ['fr' => 'Éditeur de ChatGPT et des modèles GPT.', 'en' => 'The company behind ChatGPT and the GPT models.']],
            ['name' => 'WordPress', 'category' => 'cms', 'icon' => 'wordpress', 'description' => ['fr' => 'CMS le plus utilisé pour créer des sites web.', 'en' => 'The most widely used CMS for building websites.']],
            ['name' => 'Elementor', 'category' => 'cms', 'icon' => 'elementor', 'description' => ['fr' => 'Constructeur de pages visuel pour WordPress.', 'en' => 'Visual page builder for WordPress.']],
            ['name' => 'Divi', 'category' => 'cms', 'icon' => 'divi', 'description' => ['fr' => 'Thème et constructeur visuel pour WordPress.', 'en' => 'WordPress theme and visual page builder.']],
            ['name' => 'Alphabet', 'category' => 'ia', 'icon' => 'google', 'description' => ['fr' => 'Alphabet, la maison mère de Google : Gemini, Cloud, outils.', 'en' => "Alphabet, Google's parent company: Gemini, Cloud, tools."]],
            ['name' => 'PhpStorm', 'category' => 'qualite', 'icon' => 'phpstorm', 'description' => ['fr' => 'IDE PHP de JetBrains, mon environnement de travail.', 'en' => "JetBrains' PHP IDE, my daily working environment."]],
            ['name' => 'Cursor', 'category' => 'ia', 'icon' => 'cursor', 'description' => ['fr' => "Éditeur de code pensé pour programmer avec l'IA.", 'en' => 'Code editor built for programming with AI.']],
            ['name' => 'Ubuntu', 'category' => 'infra', 'icon' => 'ubuntu', 'description' => ['fr' => 'Distribution Linux populaire pour serveurs et postes de travail.', 'en' => 'Popular Linux distribution for servers and workstations.']],
            ['name' => 'Debian', 'category' => 'infra', 'icon' => 'debian', 'description' => ['fr' => 'Distribution Linux stable, base de nombreux serveurs.', 'en' => 'Stable Linux distribution powering countless servers.']],
            ['name' => 'Apache', 'category' => 'infra', 'icon' => 'apache', 'description' => ['fr' => 'Serveur web historique, souple et modulaire.', 'en' => 'Long-standing, flexible and modular web server.']],
            ['name' => 'MikroTik', 'category' => 'infra', 'icon' => 'mikrotik', 'description' => ['fr' => 'Routeurs et réseau professionnels (RouterOS).', 'en' => 'Professional routers and networking gear (RouterOS).']],
            ['name' => 'WireGuard', 'category' => 'infra', 'icon' => 'wireguard', 'description' => ['fr' => 'VPN moderne, rapide et simple à configurer.', 'en' => 'Modern VPN, fast and simple to configure.']],
            ['name' => 'Tailscale', 'category' => 'infra', 'icon' => 'tailscale', 'description' => ['fr' => 'Réseau privé maillé basé sur WireGuard, sans configuration.', 'en' => 'Zero-config mesh VPN built on WireGuard.']],
            ['name' => 'PHPUnit', 'category' => 'qualite', 'icon' => 'phpunit', 'description' => ['fr' => 'Framework de tests unitaires de référence pour PHP.', 'en' => 'The reference unit-testing framework for PHP.']],
            ['name' => 'Git', 'category' => 'qualite', 'icon' => 'git', 'description' => ['fr' => 'Gestion de versions décentralisée pour le code source.', 'en' => 'Distributed version control for source code.']],
            ['name' => 'GitHub', 'category' => 'qualite', 'icon' => 'github', 'description' => ['fr' => 'Plateforme pour héberger, partager et relire du code.', 'en' => 'Platform to host, share and review code.']],
            ['name' => 'GitLab', 'category' => 'qualite', 'icon' => 'gitlab', 'description' => ['fr' => 'Plateforme DevOps : dépôts, CI/CD et suivi de projet.', 'en' => 'DevOps platform: repositories, CI/CD and project tracking.']],
            ['name' => 'Flutter', 'category' => 'frameworks', 'icon' => 'flutter', 'description' => ['fr' => 'Framework Google pour des applications mobiles multiplateformes.', 'en' => "Google's framework for cross-platform mobile apps."]],
            ['name' => 'React Native', 'category' => 'frameworks', 'icon' => 'react', 'description' => ['fr' => 'Bibliothèque pour construire des interfaces interactives.', 'en' => 'Library for building interactive user interfaces.']],
            ['name' => 'Vue.js', 'category' => 'frameworks', 'icon' => 'vuejs', 'description' => ['fr' => 'Framework JavaScript progressif pour interfaces réactives.', 'en' => 'Progressive JavaScript framework for reactive interfaces.']],
            ['name' => 'Linux', 'category' => 'infra', 'icon' => 'linux', 'description' => ['fr' => "Système d'exploitation libre qui fait tourner l'essentiel des serveurs.", 'en' => "Open-source operating system running most of the world's servers."]],
            ['name' => 'Zabbix', 'category' => 'infra', 'icon' => 'zabbix', 'description' => ['fr' => "Supervision d'infrastructure : métriques, alertes, disponibilité.", 'en' => 'Infrastructure monitoring: metrics, alerts, availability.']],
            ['name' => 'GLPI', 'category' => 'infra', 'icon' => 'glpi', 'description' => ['fr' => 'Gestion de parc informatique et centre de support (ITSM).', 'en' => 'IT asset management and service desk (ITSM).']],
            ['name' => 'MariaDB', 'category' => 'donnees', 'icon' => 'mariadb', 'description' => ['fr' => 'Base de données relationnelle libre, dérivée de MySQL.', 'en' => 'Open-source relational database forked from MySQL.']],
            ['name' => 'SQLite', 'category' => 'donnees', 'icon' => 'sqlite', 'description' => ['fr' => 'Base de données légère stockée dans un simple fichier.', 'en' => 'Lightweight database stored in a single file.']],
            ['name' => 'Vitest', 'category' => 'qualite', 'icon' => 'vitest', 'description' => ['fr' => 'Framework de tests rapide pour projets Vite et TypeScript.', 'en' => 'Fast test framework for Vite and TypeScript projects.']],
            ['name' => 'Angular', 'category' => 'frameworks', 'icon' => 'angular', 'description' => ['fr' => 'Framework TypeScript de Google pour applications web complètes.', 'en' => "Google's TypeScript framework for full-featured web apps."]],
            ['name' => 'NativePHP', 'category' => 'frameworks', 'icon' => 'nativephp', 'description' => ['fr' => 'Crée des applications de bureau et mobiles avec PHP et Laravel.', 'en' => 'Build desktop and mobile apps with PHP and Laravel.']],
        ];

        Technology::query()->withTrashed()->whereIn('name', ['Nginx Proxy Manager', 'Claude Code'])->forceDelete();

        $categoryIds = TechnologyCategory::query()->pluck('id', 'key');

        foreach ($technologies as $technology) {
            $description = Arr::pull($technology, 'description');
            $technology['category_id'] = $categoryIds[Arr::pull($technology, 'category')];

            $record = Technology::query()->updateOrCreate(['name' => $technology['name']], $technology);

            // La description est modifiable en admin : on ne la (re)définit qu'à la création,
            // jamais en la réécrasant sur une fiche déjà seedée (potentiellement déjà éditée).
            if ($record->wasRecentlyCreated) {
                $record->update(['description' => $description]);
            }
        }
    }
}

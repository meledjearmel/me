<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Complète la description des technologies déjà seedées avant l'ajout de cette colonne
     * (voir 2026_09_28_120000_add_description_to_technologies_table). Ne touche jamais une
     * ligne qui a déjà une description (donc jamais une modification faite depuis l'admin) :
     * cette migration ne s'applique qu'une fois, contrairement à TechnologySeeder qui peut être
     * relancé sans effet sur les fiches existantes.
     *
     * Les textes sont recopiés ici plutôt que lus depuis le seeder : une migration ne doit pas
     * dépendre d'un fichier que l'app peut faire évoluer après coup.
     */
    public function up(): void
    {
        $descriptions = [
            'PHP' => ['fr' => 'Langage serveur au cœur de mes applications web.', 'en' => 'Server-side language at the core of my web apps.'],
            'TypeScript' => ['fr' => 'JavaScript typé : moins de bugs, un code plus sûr.', 'en' => 'Typed JavaScript: fewer bugs, safer code.'],
            'Laravel' => ['fr' => 'Framework PHP pour bâtir des applications web solides.', 'en' => 'PHP framework for building solid web applications.'],
            'Inertia' => ['fr' => 'Relie Laravel et React sans API à maintenir.', 'en' => 'Connects Laravel and React without maintaining an API.'],
            'React' => ['fr' => 'Bibliothèque pour construire des interfaces interactives.', 'en' => 'Library for building interactive user interfaces.'],
            'PostgreSQL' => ['fr' => 'Base de données relationnelle robuste et fiable.', 'en' => 'Robust and reliable relational database.'],
            'MySQL' => ['fr' => 'Base de données relationnelle très répandue.', 'en' => 'Widely used relational database.'],
            'Redis' => ['fr' => "Base en mémoire : cache, files d'attente, sessions.", 'en' => 'In-memory store: cache, queues, sessions.'],
            'Pest' => ['fr' => 'Framework de tests PHP simple et lisible.', 'en' => 'Simple, readable PHP testing framework.'],
            'PHPStan' => ['fr' => "Analyse statique qui repère les erreurs avant l'exécution.", 'en' => 'Static analysis that catches errors before runtime.'],
            'Fortify (2FA)' => ['fr' => 'Authentification à deux facteurs pour Laravel.', 'en' => 'Two-factor authentication for Laravel.'],
            'Proxmox' => ['fr' => 'Plateforme de virtualisation pour héberger mes serveurs.', 'en' => 'Virtualization platform to host my servers.'],
            'Docker' => ['fr' => 'Conteneurs pour des environnements reproductibles.', 'en' => 'Containers for reproducible environments.'],
            'Nginx' => ['fr' => 'Serveur web et reverse proxy performant.', 'en' => 'High-performance web server and reverse proxy.'],
            'Ollama' => ['fr' => "Fait tourner des modèles d'IA en local.", 'en' => 'Runs AI models locally.'],
            'Vite' => ['fr' => 'Outil de build front-end ultra rapide.', 'en' => 'Blazing-fast front-end build tool.'],
            'TailwindCSS' => ['fr' => 'Framework CSS utilitaire pour styliser vite.', 'en' => 'Utility-first CSS framework for styling fast.'],
            'Alpine.js' => ['fr' => "Un peu d'interactivité légère directement dans le HTML.", 'en' => 'Lightweight interactivity right in the HTML.'],
            'Livewire' => ['fr' => 'Interfaces dynamiques en Laravel, sans écrire de JS.', 'en' => 'Dynamic Laravel interfaces without writing JS.'],
            'Expo' => ['fr' => 'Plateforme pour créer des applications mobiles React Native.', 'en' => 'Platform for building React Native mobile apps.'],
            'JS Vanilla' => ['fr' => 'Le langage du navigateur, utilisé sans framework.', 'en' => 'The language of the browser, used without a framework.'],
            'Node.js' => ['fr' => 'Environnement pour exécuter JavaScript côté serveur.', 'en' => 'Runtime to execute JavaScript on the server.'],
            'Figma' => ['fr' => "Outil de design d'interfaces et de prototypage collaboratif.", 'en' => 'Collaborative interface design and prototyping tool.'],
            'Anthropic AI' => ['fr' => "Assistant IA d'Anthropic (Claude) pour coder et rédiger.", 'en' => "Anthropic's AI assistant (Claude) for coding and writing."],
            'OpenAI' => ['fr' => 'Éditeur de ChatGPT et des modèles GPT.', 'en' => 'The company behind ChatGPT and the GPT models.'],
            'WordPress' => ['fr' => 'CMS le plus utilisé pour créer des sites web.', 'en' => 'The most widely used CMS for building websites.'],
            'Elementor' => ['fr' => 'Constructeur de pages visuel pour WordPress.', 'en' => 'Visual page builder for WordPress.'],
            'Divi' => ['fr' => 'Thème et constructeur visuel pour WordPress.', 'en' => 'WordPress theme and visual page builder.'],
            'Alphabet' => ['fr' => 'Alphabet, la maison mère de Google : Gemini, Cloud, outils.', 'en' => "Alphabet, Google's parent company: Gemini, Cloud, tools."],
            'PhpStorm' => ['fr' => 'IDE PHP de JetBrains, mon environnement de travail.', 'en' => "JetBrains' PHP IDE, my daily working environment."],
            'Cursor' => ['fr' => "Éditeur de code pensé pour programmer avec l'IA.", 'en' => 'Code editor built for programming with AI.'],
            'Ubuntu' => ['fr' => 'Distribution Linux populaire pour serveurs et postes de travail.', 'en' => 'Popular Linux distribution for servers and workstations.'],
            'Debian' => ['fr' => 'Distribution Linux stable, base de nombreux serveurs.', 'en' => 'Stable Linux distribution powering countless servers.'],
            'Apache' => ['fr' => 'Serveur web historique, souple et modulaire.', 'en' => 'Long-standing, flexible and modular web server.'],
            'MikroTik' => ['fr' => 'Routeurs et réseau professionnels (RouterOS).', 'en' => 'Professional routers and networking gear (RouterOS).'],
            'WireGuard' => ['fr' => 'VPN moderne, rapide et simple à configurer.', 'en' => 'Modern VPN, fast and simple to configure.'],
            'Tailscale' => ['fr' => 'Réseau privé maillé basé sur WireGuard, sans configuration.', 'en' => 'Zero-config mesh VPN built on WireGuard.'],
            'PHPUnit' => ['fr' => 'Framework de tests unitaires de référence pour PHP.', 'en' => 'The reference unit-testing framework for PHP.'],
            'Git' => ['fr' => 'Gestion de versions décentralisée pour le code source.', 'en' => 'Distributed version control for source code.'],
            'GitHub' => ['fr' => 'Plateforme pour héberger, partager et relire du code.', 'en' => 'Platform to host, share and review code.'],
            'GitLab' => ['fr' => 'Plateforme DevOps : dépôts, CI/CD et suivi de projet.', 'en' => 'DevOps platform: repositories, CI/CD and project tracking.'],
            'Flutter' => ['fr' => 'Framework Google pour des applications mobiles multiplateformes.', 'en' => "Google's framework for cross-platform mobile apps."],
            'React Native' => ['fr' => 'Bibliothèque pour construire des interfaces interactives.', 'en' => 'Library for building interactive user interfaces.'],
            'Vue.js' => ['fr' => 'Framework JavaScript progressif pour interfaces réactives.', 'en' => 'Progressive JavaScript framework for reactive interfaces.'],
            'Linux' => ['fr' => "Système d'exploitation libre qui fait tourner l'essentiel des serveurs.", 'en' => "Open-source operating system running most of the world's servers."],
            'Zabbix' => ['fr' => "Supervision d'infrastructure : métriques, alertes, disponibilité.", 'en' => 'Infrastructure monitoring: metrics, alerts, availability.'],
            'GLPI' => ['fr' => 'Gestion de parc informatique et centre de support (ITSM).', 'en' => 'IT asset management and service desk (ITSM).'],
            'MariaDB' => ['fr' => 'Base de données relationnelle libre, dérivée de MySQL.', 'en' => 'Open-source relational database forked from MySQL.'],
            'SQLite' => ['fr' => 'Base de données légère stockée dans un simple fichier.', 'en' => 'Lightweight database stored in a single file.'],
            'Vitest' => ['fr' => 'Framework de tests rapide pour projets Vite et TypeScript.', 'en' => 'Fast test framework for Vite and TypeScript projects.'],
            'Angular' => ['fr' => 'Framework TypeScript de Google pour applications web complètes.', 'en' => "Google's TypeScript framework for full-featured web apps."],
            'NativePHP' => ['fr' => 'Crée des applications de bureau et mobiles avec PHP et Laravel.', 'en' => 'Build desktop and mobile apps with PHP and Laravel.'],
        ];

        foreach ($descriptions as $name => $description) {
            DB::table('technologies')
                ->where('name', $name)
                ->whereNull('description')
                ->update(['description' => json_encode($description)]);
        }
    }

    /**
     * Rien à défaire : un vide de la colonne perdrait aussi une description saisie depuis,
     * ce que cette migration n'a justement jamais le droit de faire.
     */
    public function down(): void {}
};

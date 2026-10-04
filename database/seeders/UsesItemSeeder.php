<?php

namespace Database\Seeders;

use App\Enums\UsesCategory;
use App\Models\UsesItem;
use Illuminate\Database\Seeder;

/**
 * Premier contenu de la page « Uses », à compléter et corriger depuis l'administration.
 * N'écrit rien si la page a déjà du contenu : un re-seed n'écrase jamais mes modifications.
 */
class UsesItemSeeder extends Seeder
{
    public function run(): void
    {
        if (UsesItem::withTrashed()->exists()) {
            return;
        }

        foreach ($this->items() as $category => $items) {
            foreach ($items as $order => [$name, $url, $fr, $en]) {
                UsesItem::query()->create([
                    'category' => UsesCategory::from($category),
                    'name' => $name,
                    'url' => $url,
                    'description' => ['fr' => $fr, 'en' => $en],
                    'sort_order' => $order,
                ]);
            }
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
                ['Claude Code', 'https://claude.com/claude-code', 'Mon assistant de développement : il lit le projet, écrit le code et lance les tests avec moi.', 'My development assistant: it reads the project, writes code and runs the tests with me.'],
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

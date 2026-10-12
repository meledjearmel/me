<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Description('Rédiger le brouillon de l\'étude de cas du projet ouvert (dépôt courant), en français.')]
class DraftProjectPrompt extends Prompt
{
    public function handle(Request $request): Response
    {
        $notes = filled($request->get('notes')) ? "\n\nNotes du développeur, à prendre en compte en priorité :\n{$request->get('notes')}" : '';

        $guide = PlanProjectImagesPrompt::GUIDE;

        return Response::text(<<<TEXT
        Rédige le brouillon de l'étude de cas de ce projet pour mon portfolio.

        1. Explore le dépôt courant : README, manifestes (composer.json, package.json, pubspec.yaml…), structure du code, historique git (dates du premier et du dernier commit, nombre de contributeurs), et ses remotes (« git remote -v »). Seul GitHub compte pour mon portfolio : si le dépôt est sur GitLab ou ailleurs, cherche son miroir dans github_repositories de get-catalog et demande-moi confirmation, ou demande-moi l'équivalent GitHub.
        2. Avant d'écrire, pose-moi en une seule fois les questions utiles (cinq au plus) sur ce que le dépôt ne dit pas : client et contexte, mon rôle exact, période, résultat ou chiffres, ce qui doit rester confidentiel. Attends mes réponses ; ce que je ne sais pas reste vide.
        3. Appelle get-catalog. Si une technologie importante du projet manque au site, crée-la avec create-technology.
        4. S'il s'agit d'une app liée à un projet déjà présent (back-office, nouvelle version, app compagnon), cite-la avec son jeton de mention et renseigne related_projects.
        5. Enregistre le brouillon avec save-project-draft, en français, sans rien inventer.
        6. Donne-moi le lien du brouillon, la liste de ce que tu n'as pas pu déterminer (client, chiffres, dates…), et le plan d'images ci-dessous.

        {$guide}
        {$notes}
        TEXT);
    }

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('notes', 'Ce que le dépôt ne dit pas : client, contexte, chiffres, dates…'),
        ];
    }
}

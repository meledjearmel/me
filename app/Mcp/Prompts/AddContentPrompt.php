<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('add-content')]
#[Description('Ajouter en brouillon une expérience, une formation, une certification, un élément « Uses », une compétence ou une offre (page « Services »), à partir d\'une source (CV, profil, lien, notes).')]
class AddContentPrompt extends Prompt
{
    public function handle(Request $request): Response
    {
        $request->validate([
            'type' => ['required', 'in:experience,education,certification,distinction,uses_item,skill,service'],
            'source' => ['nullable', 'string'],
        ]);

        $source = filled($request->get('source')) ? "\nSource : {$request->get('source')}" : '';

        return Response::text(<<<TEXT
        Ajoute à mon portfolio, en brouillon et en français, un contenu de type « {$request->get('type')} ».{$source}

        1. Si la source est une adresse web, lis-la avec read-web-page ; si c'est un fichier ou du texte, lis-le.
        2. Appelle list-contents pour ce type : la description de ses champs, et les contenus existants pour ne pas créer de doublon (s'il existe déjà, propose plutôt de modifier son brouillon).
        3. Pose-moi en une seule fois les questions utiles (cinq au plus) sur ce que la source ne dit pas : dates exactes, intitulé, réalisations chiffrées, ce qui doit rester confidentiel. Attends mes réponses ; ce que je ne sais pas reste vide.
        4. Enregistre avec save-content-draft, sans rien inventer. Pour une compétence ou une expérience, appuie-toi sur get-catalog (domaines, technologies, éléments à citer) ; pour une offre, sur get-catalog (projets à relier) et get-estimator (type de projet pour le prix).
        5. Donne-moi le lien du brouillon et ce que je dois vérifier.
        TEXT);
    }

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('type', 'experience, education, certification, distinction, uses_item, skill ou service.', required: true),
            new Argument('source', 'Adresse web, texte collé ou chemin de fichier (CV, profil, page de certification…).'),
        ];
    }
}

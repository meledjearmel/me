<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Description('Traduire en anglais un brouillon de projet dont le français est validé.')]
class TranslateProjectPrompt extends Prompt
{
    public function handle(Request $request): Response
    {
        $request->validate(['id' => ['required', 'integer']]);

        return Response::text(<<<TEXT
        Le français du brouillon de projet #{$request->get('id')} est validé : traduis-le en anglais.

        1. Lis-le avec get-project.
        2. Traduis chaque champ rempli en anglais naturel et professionnel, sans rien ajouter ni retirer. Garde les jetons de mention (@[Libellé](type:id)) : traduis seulement le libellé s'il le faut, jamais le type ni l'identifiant.
        3. Enregistre avec save-project-translation, en gardant l'ordre des choix techniques et des chiffres clés.
        4. Donne-moi le lien du brouillon pour une dernière relecture avant publication.
        TEXT);
    }

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('id', 'Identifiant du brouillon (donné par save-project-draft).', required: true),
        ];
    }
}

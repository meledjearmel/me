<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('plan-project-images')]
#[Description('Proposer les images d\'un brouillon de projet : couverture et galerie, quoi capturer, et un prompt professionnel pour celles qui manquent.')]
class PlanProjectImagesPrompt extends Prompt
{
    /** Consignes du plan d'images d'un projet, reprises par le prompt de rédaction. */
    public const string GUIDE = <<<'TEXT'
    Plan d'images du projet (une étude de cas se juge d'abord sur ses visuels) :
    - Couverture (16:9, 1600×900, affichée sur les cartes et en tête de l'étude de cas) : de préférence l'écran le plus parlant du projet, mis en scène dans un cadre d'appareil (navigateur, téléphone) sur un fond sobre ; dis exactement quel écran capturer et avec quelles données de démonstration.
    - Galerie (trois à six images) : les écrans ou moments clés dans l'ordre du parcours (accueil, fonction phare, détail technique visible, résultat), chacun avec ce qu'il montre en une phrase.
    - Pour chaque image : capture réelle de préférence (je la fais moi-même : quoi, quelle taille de fenêtre, quelles données fictives) ; sinon, un prompt de génération professionnel en anglais prêt à copier (sujet, composition, style sobre et moderne cohérent d'une image à l'autre, palette limitée, format, et à éviter : texte incrusté illisible, logos de marques, visages réalistes, clichés « néon cyberpunk »).
    - Rappelle ce qui ne doit jamais apparaître : données réelles de clients, noms, e-mails, clés ou adresses internes.
    Quand je te fournis les images, ajoute-les avec add-project-image (cover, puis gallery dans l'ordre).
    TEXT;

    public function handle(Request $request): Response
    {
        $request->validate(['id' => ['required', 'integer']]);

        return Response::text("Lis le brouillon de projet #{$request->get('id')} avec get-project (et le dépôt courant s'il s'agit de ce projet), puis propose-moi son plan d'images.\n\n".self::GUIDE);
    }

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('id', 'Identifiant du brouillon de projet.', required: true),
        ];
    }
}

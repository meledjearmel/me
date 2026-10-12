<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('plan-post-images')]
#[Description('Proposer les images d\'un brouillon d\'article : où en mettre, de quel type, et un prompt professionnel pour générer celles qui manquent.')]
class PlanPostImagesPrompt extends Prompt
{
    /** Consignes du plan d'images, reprises par le prompt d'écriture d'un article. */
    public const string GUIDE = <<<'TEXT'
    Plan d'images : pour la couverture puis chaque endroit du contenu qui gagnerait vraiment à être illustré (trois ou quatre au plus, jamais par décoration), donne :
    - l'emplacement (« couverture », « après le titre « … » ») ;
    - le type d'image le plus juste : capture réelle (interface, terminal, résultat), schéma (architecture, flux, avant/après), extrait de code mis en forme, ou illustration ;
    - pourquoi elle aide le lecteur, en une phrase ;
    - si c'est une capture réelle : exactement quoi capturer et comment la cadrer (je la fais moi-même) ;
    - sinon, un prompt de génération professionnel, en anglais, prêt à copier : sujet précis, composition, style (sobre, moderne, éditorial tech, cohérent d'une image à l'autre), palette limitée, éclairage, format (couverture en 16:9, 1600×900 ; contenu en 16:9 ou 4:3), et ce qu'il faut éviter (texte incrusté, logos de marques, visages réalistes, effets clichés « néon cyberpunk ») ;
    - le texte alternatif (alt) en français.
    Un schéma se fait mieux à la main (Excalidraw, Mermaid) qu'en génération : propose alors son contenu (blocs et flèches) plutôt qu'un prompt.
    Quand je te fournis les images, ajoute-les avec add-post-image et insère les balises renvoyées aux emplacements prévus.
    TEXT;

    public function handle(Request $request): Response
    {
        $request->validate(['id' => ['required', 'integer']]);

        return Response::text("Lis le brouillon d'article #{$request->get('id')} avec get-post, puis propose-moi son plan d'images.\n\n".self::GUIDE);
    }

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('id', 'Identifiant du brouillon d\'article.', required: true),
        ];
    }
}

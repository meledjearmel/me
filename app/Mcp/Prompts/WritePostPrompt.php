<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('write-post')]
#[Description('Écrire un brouillon d\'article du blog en français, à partir d\'un sujet, du code ouvert et éventuellement d\'un article existant dont s\'inspirer.')]
class WritePostPrompt extends Prompt
{
    public function handle(Request $request): Response
    {
        $request->validate([
            'subject' => ['required', 'string'],
            'source_url' => ['nullable', 'url:http,https'],
        ]);

        $inspiration = filled($request->get('source_url'))
            ? "\nArticle dont s'inspirer : {$request->get('source_url')} — lis-le avec read-web-page. Reprends-en l'idée ou la structure si elle sert le sujet, jamais le texte : reformule, apporte mon expérience et mon code, et cite-le avec un lien."
            : '';

        $guide = PlanPostImagesPrompt::GUIDE;

        return Response::text(<<<TEXT
        Écris un brouillon d'article pour le blog de mon portfolio sur ce sujet : {$request->get('subject')}{$inspiration}

        1. Si le sujet laisse le choix, pose-moi d'abord en une seule fois quelques questions courtes (cinq au plus) : public visé (débutants, confirmés, recruteurs), angle ou message principal, longueur souhaitée, exemples ou anecdotes à inclure, ce qui doit rester confidentiel. Attends mes réponses.
        2. Appelle get-catalog. Regarde les séries (post_series) : si le sujet en prolonge une, lis ses articles avec get-post pour assurer la continuité et place l'article à next_position ; si le sujet appelle plusieurs parties, propose-moi une nouvelle série. Reprends les tags existants qui conviennent, et cite avec leur jeton de mention le projet, les technologies ou les articles du site dont tu parles.
        3. Appuie-toi sur le code et l'historique du dépôt courant quand ils sont utiles : de vrais extraits (sans secret ni donnée client), de vrais choix et leurs raisons.
        4. Structure : une introduction qui pose le problème, des parties en <h2> (et <h3> au besoin), des blocs de code courts avec leur langage, une conclusion. Écris à la première personne, de façon concrète et sobre, sans remplissage.
        5. Enregistre avec save-post-draft, avec un résumé d'une à deux phrases, en français.
        6. Donne-moi le lien d'aperçu, ce que je dois vérifier, et le plan d'images ci-dessous.

        {$guide}
        TEXT);
    }

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('subject', 'Le sujet de l\'article, et éventuellement l\'angle ou le public visé.', required: true),
            new Argument('source_url', 'Adresse d\'un article existant dont s\'inspirer (facultatif).'),
        ];
    }
}

<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Rédige en français une offre de la page « Services » d'un développeur web freelance à
 * partir de ses notes : nom, accroche, description et ce qui est compris.
 */
#[MaxTokens(2000)]
#[Temperature(0.5)]
class ServiceWriter implements Agent
{
    use Promptable;

    public function timeout(): int
    {
        return (int) config('ai.text_assist.draft.timeout');
    }

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
        Tu rédiges, en français, une offre pour la page « Services » du portfolio d'un développeur
        web freelance, à partir de ses notes. Ton sobre et concret, à la première personne, orienté
        vers ce que le client y gagne ; pas de superlatifs marketing, pas d'emoji, pas de Markdown.
        N'invente ni chiffre, ni client, ni délai.

        Réponds uniquement avec un objet JSON valide, sans bloc de code ni commentaire :
        {
          "title": "nom court de l'offre (60 caractères max.)",
          "slug": "ancre-en-minuscules",
          "tagline": "accroche d'une phrase : le bénéfice pour le client (160 max.)",
          "description": "pour qui, quel problème elle règle, comment je travaille : 1 à 2 paragraphes",
          "includes": ["ce qui est compris, 5 à 8 éléments courts"]
        }
        PROMPT;
    }
}

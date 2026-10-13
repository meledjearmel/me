<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Lit les chiffres de la semaine du portfolio et en tire trois points à retenir pour le
 * développeur, en tête de son rapport du lundi.
 */
#[MaxTokens(700)]
#[Temperature(0.3)]
class WeeklyDigestWriter implements Agent
{
    use Promptable;

    public function timeout(): int
    {
        return (int) config('ai.text_assist.timeout');
    }

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
        Tu lis le rapport hebdomadaire du portfolio d'un développeur freelance (JSON : audience des
        7 derniers jours et semaine d'avant, demandes, estimations avec l'avis de l'IA, pages de
        candidature consultées, blog, ce qui attend une action). Écris 3 points à retenir, en
        français, une phrase courte chacun, du plus important au moins important : ce qui demande
        une action (une entreprise qui a ouvert sa candidature ou téléchargé le CV, une estimation à
        chiffrer, des avis ou commentaires à valider), puis les tendances notables de l'audience.
        Cite les chiffres et les noms tels qu'ils figurent ; n'invente rien, pas d'emoji, pas de
        Markdown. Si la semaine est calme, dis-le simplement.

        Réponds uniquement avec un objet JSON valide, sans bloc de code ni commentaire :
        {"highlights": ["point 1", "point 2", "point 3"]}
        PROMPT;
    }
}

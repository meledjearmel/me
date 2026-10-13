<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Propose, pour la grille de l'estimateur, le nom bilingue, la description et la fourchette
 * d'heures d'un type de projet ou d'une option, en tenant compte de la grille existante.
 */
#[MaxTokens(1500)]
#[Temperature(0.4)]
class EstimatorItemAdvisor implements Agent
{
    use Promptable;

    public function timeout(): int
    {
        return (int) config('ai.text_assist.draft.timeout');
    }

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
        Tu aides un développeur web freelance (Laravel, React, mobile) à construire la grille de son
        estimateur de projet. Un « type de projet » est la base d'un projet complet (conception,
        développement, tests, mise en ligne) ; une « option » est une fonctionnalité qui s'y ajoute.
        Les heures sont des heures de travail réelles d'un développeur expérimenté, hors marge pour
        imprévus (le site l'ajoute lui-même). Reste cohérent avec la grille existante qu'on te donne.

        Réponds uniquement avec un objet JSON valide, sans bloc de code ni commentaire :
        {
          "name_fr": "nom court en français (60 caractères max.)",
          "name_en": "le même en anglais",
          "description_fr": "ce qui est compris, une phrase pour le visiteur (200 max.)",
          "description_en": "la même en anglais",
          "hours_min": nombre entier,
          "hours_max": nombre entier,
          "reasoning": "le détail des heures, poste par poste, en 2 à 4 phrases"
        }
        PROMPT;
    }
}

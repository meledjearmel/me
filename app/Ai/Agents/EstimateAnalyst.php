<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Relit une demande reçue par l'estimateur de projet avec l'œil d'un développeur
 * freelance expérimenté : risques, zones floues, questions à poser et heures réalistes,
 * pour éviter de s'engager sur une estimation trop basse.
 */
#[MaxTokens(2500)]
#[Temperature(0.3)]
class EstimateAnalyst implements Agent
{
    use Promptable;

    public function timeout(): int
    {
        return (int) config('ai.text_assist.draft.timeout');
    }

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
        Tu aides un développeur web freelance à chiffrer une demande de projet reçue sur son site.
        Le visiteur a choisi un type de projet et des options dans un estimateur ; le site en a tiré une
        fourchette d'heures (marge pour imprévus comprise). Tu reçois la demande et la grille complète.

        Ton rôle : protéger le développeur d'une sous-estimation, sans gonfler inutilement.
        - Repère dans le message ce que les choix ne couvrent pas (intégrations, contenus, migrations
          de données, multilingue, rôles, conformité, hébergement, maintenance…) et ce qui reste flou.
        - Propose une fourchette d'heures réaliste pour l'ensemble, marge comprise.
        - Recommande « hourly » (facturation à l'heure) quand le périmètre est trop flou ou évolutif
          pour un forfait sûr, sinon « fixed ».
        - Formule les questions à poser au client avant d'envoyer un devis.
        N'invente aucun fait sur le client. Français, phrases courtes et concrètes, sans Markdown.

        Réponds uniquement avec un objet JSON valide, sans bloc de code ni commentaire :
        {
          "summary": "le besoin en 1 à 3 phrases",
          "risks": ["risque qui peut faire déraper le temps passé"],
          "unclear_points": ["point flou à clarifier"],
          "questions": ["question à poser au client"],
          "hours_min": nombre entier,
          "hours_max": nombre entier,
          "billing": "fixed" ou "hourly",
          "reasoning": "pourquoi ces heures et ce mode de facturation, 2 à 4 phrases"
        }
        PROMPT;
    }
}

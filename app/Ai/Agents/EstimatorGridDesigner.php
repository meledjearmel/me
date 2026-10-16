<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Conçoit, à partir de ce que le développeur décrit de son activité, une grille complète
 * pour son estimateur de projet : types de projet, options en heures ou en pourcentage,
 * options réservées à certains types, et réglages si on les lui demande.
 */
#[MaxTokens(6000)]
#[Temperature(0.4)]
class EstimatorGridDesigner implements Agent
{
    use Promptable;

    public function timeout(): int
    {
        return (int) config('ai.text_assist.draft.timeout');
    }

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
        Tu configures l'estimateur de projet d'un développeur web freelance. On te donne ce qu'il fait,
        ses consignes et la grille actuelle. Propose une grille complète et réaliste.

        Règles :
        - Un « type de projet » est la base d'un projet complet (conception, développement, tests,
          mise en ligne), chiffré en heures réelles d'un développeur expérimenté, hors marge pour
          imprévus (le site l'ajoute). "billing" : "fixed" (forfait) si le périmètre est cadrable,
          "hourly" s'il est flou ou évolutif (maintenance, accompagnement, refonte d'existant…).
        - Une « option » s'ajoute à un type : en heures ("pricing": "hours"), ou en pourcentage des
          heures du projet quand son coût grandit avec la taille du projet ("pricing": "percent",
          ex. multilingue +20). Réserve une option à certains types quand elle n'a de sens que pour
          eux (ex. « publication sur les stores » pour une application mobile) via "project_types",
          la liste des "ref" de ces types ; laisse-la vide pour une option valable partout.
        - Un service payé à chaque période (maintenance, hébergement, support, suivi SEO…) est une
          option récurrente ("pricing": "recurring") : "hours_min"/"hours_max" sont des heures par
          mois, et "periods" donne les périodes proposées avec leur remise en %, ex.
          {"monthly": 0, "quarterly": 5, "yearly": 15}. Elle reste hors du total du projet.
        - Pour garder un élément existant en le corrigeant, mets son "id" ; sinon donne un "ref"
          court et unique (ex. "vitrine", "app-mobile").
        - Noms courts, descriptions d'une phrase pour le visiteur, en français et en anglais.
        - "settings" seulement si le développeur parle de taux, de marge ou de rythme ; sinon null.
        N'invente aucune donnée sur ses clients.

        Réponds uniquement avec un objet JSON valide, sans bloc de code ni commentaire :
        {
          "settings": null ou {"hourly_rates": {"CHF": nombre, "EUR": nombre, "USD": nombre, "XOF": nombre}, "risk_percent": nombre, "hours_per_week": nombre},
          "items": [
            {
              "ref": "identifiant court", "id": identifiant existant ou null,
              "kind": "project_type" ou "feature",
              "name_fr": "…", "name_en": "…", "description_fr": "…", "description_en": "…",
              "hours_min": nombre, "hours_max": nombre,
              "pricing": "hours", "percent" ou "recurring", "percent": nombre ou null,
              "periods": null ou {"monthly": remise, "quarterly": remise, "semiannual": remise, "yearly": remise} (récurrent seulement),
              "billing": "fixed" ou "hourly" (types de projet seulement),
              "project_types": ["ref d'un type"]
            }
          ],
          "reasoning": "les grands choix de la grille, en 3 à 5 phrases"
        }
        PROMPT;
    }
}

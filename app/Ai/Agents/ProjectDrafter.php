<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Propose, en français, l'étude de cas d'un projet du portfolio à partir d'une source
 * (dépôt GitHub, notes en vrac), en JSON, pour pré-remplir le formulaire de l'admin.
 * Les technologies, domaines et profils sont choisis parmi ceux du site.
 */
#[MaxTokens(4000)]
#[Temperature(0.5)]
class ProjectDrafter implements Agent
{
    use Promptable;

    /**
     * @param  array{technologies: array<int, string>, domains: array<int, string>, job_profiles: array<int, string>}  $catalog  Libellés indexés par identifiant.
     */
    public function __construct(private readonly array $catalog) {}

    public function timeout(): int
    {
        return (int) config('ai.text_assist.draft.timeout');
    }

    public function instructions(): Stringable|string
    {
        $technologies = $this->list($this->catalog['technologies']);
        $domains = $this->list($this->catalog['domains']);
        $jobProfiles = $this->list($this->catalog['job_profiles']);

        return <<<PROMPT
        Tu rédiges, en français, l'étude de cas d'un projet pour le portfolio d'un développeur web,
        à partir de la source qu'on te donne (dépôt GitHub et/ou notes). Le développeur relira tout avant d'enregistrer.

        Réponds uniquement avec un objet JSON valide, sans bloc de code Markdown ni commentaire, de cette forme :
        {
          "title": "nom du projet (255 caractères max.)",
          "slug": "identifiant-url-en-minuscules",
          "tagline": "accroche d'une phrase (160 max.) ou null",
          "role": "rôle du développeur, ex. Développeur full-stack (120 max.) ou null",
          "client": "client ou contexte, ex. Projet personnel (120 max.) ou null",
          "platform": "ex. Web, Mobile iOS et Android (120 max.) ou null",
          "context": "le contexte et le besoin, 1 à 3 paragraphes",
          "challenges": "défis et contraintes, 1 à 2 paragraphes, ou null",
          "realization": "ce qui a été construit et comment, 2 à 4 paragraphes",
          "result": "le résultat obtenu, 1 à 2 paragraphes",
          "decisions": [{"choice": "le choix technique (160 max.)", "reason": "pourquoi (600 max.)"}],
          "key_figures": [{"value": "chiffre court, ex. 12 (20 max.)", "label": "ce qu'il mesure (80 max.)"}],
          "started_on": "AAAA-MM ou null",
          "ended_on": "AAAA-MM ou null",
          "team_size": nombre ou null,
          "technologies": [identifiants],
          "domains": [identifiants],
          "job_profiles": [identifiants]
        }

        Règles :
        - N'invente rien : un fait absent de la source (client, dates, chiffres, taille d'équipe) vaut null ou une liste vide.
        - Les chiffres clés ne reprennent que des chiffres présents dans la source (4 au plus) ; les choix techniques sont 6 au plus.
        - Paragraphes en texte brut, séparés par une ligne vide (\\n\\n), sans Markdown ni HTML.
        - Ton sobre et concret, à la première personne (« j'ai conçu… »).
        - Pour technologies, domains et job_profiles, n'utilise que les identifiants des listes ci-dessous.

        Technologies (identifiant : nom) :
        {$technologies}

        Domaines :
        {$domains}

        Profils métier :
        {$jobProfiles}
        PROMPT;
    }

    /** @param  array<int, string>  $items */
    private function list(array $items): string
    {
        return collect($items)->map(fn (string $label, int $id): string => "{$id} : {$label}")->implode("\n") ?: '(aucun)';
    }
}

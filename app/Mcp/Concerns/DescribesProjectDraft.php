<?php

namespace App\Mcp\Concerns;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

/**
 * Champs d'un brouillon de projet, en français, communs à save-project-draft (champs
 * principaux obligatoires) et update-project-draft (tous facultatifs).
 */
trait DescribesProjectDraft
{
    /**
     * @return array<string, Type>
     */
    private function draftFields(JsonSchema $schema, bool $requireMainFields): array
    {
        $text = fn (string $description, ?int $max = null) => $schema->string()->description($max ? "{$description} ({$max} caractères max.)" : $description);
        $ids = fn (string $description) => $schema->array()->items($schema->integer())->description($description);
        $main = fn (Type $type): Type => $requireMainFields ? $type->required() : $type;
        $mentions = ' Peut citer un élément du site (autre app, article, technologie, expérience) avec son jeton de mention donné par get-catalog, ex. « @[App Station](project:12) ».';

        return [
            'title' => $main($text('Nom du projet', 255)),
            'slug' => $main($schema->string()->description('Identifiant d\'URL en minuscules et tirets, unique.')),
            'tagline' => $text('Accroche d\'une phrase', 160),
            'role' => $text('Rôle du développeur, ex. « Développeur full-stack »', 120),
            'client' => $text('Client ou contexte, ex. « Projet personnel »', 120),
            'platform' => $text('Plateforme, ex. « Web, Mobile iOS et Android »', 120),
            'context' => $main($text('Contexte et besoin, 1 à 3 paragraphes en texte brut séparés par une ligne vide.'.$mentions)),
            'challenges' => $text('Défis et contraintes, 1 à 2 paragraphes.'.$mentions),
            'realization' => $main($text('Ce qui a été construit et comment, 2 à 4 paragraphes, à la première personne.'.$mentions)),
            'result' => $main($text('Résultat obtenu, 1 à 2 paragraphes.'.$mentions)),
            'decisions' => $schema->array()->max(6)->items($schema->object([
                'choice' => $text('Le choix technique', 160)->required(),
                'reason' => $text('Pourquoi', 600)->required(),
            ]))->description('Choix techniques argumentés (liste complète).'),
            'key_figures' => $schema->array()->max(4)->items($schema->object([
                'value' => $text('Chiffre court, ex. « 12 »', 20)->required(),
                'label' => $text('Ce qu\'il mesure', 80)->required(),
            ]))->description('Chiffres clés réellement présents dans la source (liste complète).'),
            'started_on' => $schema->string()->description('Début, AAAA-MM.'),
            'ended_on' => $schema->string()->description('Fin, AAAA-MM.'),
            'team_size' => $schema->integer()->description('Taille de l\'équipe.'),
            'technologies' => $ids('Identifiants de technologies (get-catalog), liste complète.'),
            'domains' => $ids('Identifiants de domaines (get-catalog), liste complète.'),
            'job_profiles' => $ids('Identifiants de profils métier (get-catalog), liste complète.'),
            'related_projects' => $ids('Identifiants des projets liés (même produit, version précédente, app compagnon, back-office d\'une app…), affichés ensemble sur les pages publiques. Liste complète.'),
            'github_repository' => $schema->string()->description('Dépôt GitHub du projet (GitHub uniquement : pour un dépôt GitLab ou autre, passer son miroir GitHub confirmé par le développeur) : URL HTTPS ou SSH d\'un remote GitHub, ou « propriétaire/nom », privé compris. Rattaché aux dépôts synchronisés (github_repositories de get-catalog) ; son adresse publique est remplie seule pour un dépôt public.'),
            'repo_url' => $schema->string()->description('URL publique du dépôt, si elle diffère de GitHub ; jamais pour un dépôt privé.'),
            'demo_url' => $schema->string()->description('URL de démo ou du site en ligne.'),
            'is_open_source' => $schema->boolean()->description('Projet open source (dépôt public).'),
        ];
    }

    /** @param  list<string>  $notes */
    private function savedMessage(int $projectId, string $url, array $notes): string
    {
        return implode("\n", ["Brouillon #{$projectId} enregistré. À relire, traduire et publier dans l'administration : {$url}", ...$notes]);
    }
}

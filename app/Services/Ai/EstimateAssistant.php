<?php

namespace App\Services\Ai;

use App\Ai\Agents\EstimateAnalyst;
use App\Ai\Agents\EstimateMatcher;
use App\Ai\Agents\EstimatorItemAdvisor;
use App\Enums\EstimatorBilling;
use App\Enums\EstimatorItemKind;
use App\Models\Engagement;
use App\Models\EstimatorItem;
use App\Models\SiteSetting;

/**
 * L'IA au service de l'estimateur de projet, partagée par l'administration, l'API, la
 * page publique et le serveur MCP : analyse d'une demande reçue, suggestion d'un élément
 * de la grille, choix du type et des options d'après la description d'un visiteur.
 * Chaque réponse est ramenée à des valeurs sûres ; rien n'est publié sans relecture.
 */
class EstimateAssistant
{
    public function __construct(private readonly TextAssistService $assist) {}

    /**
     * Analyse une demande et l'enregistre sur la demande. False si aucun fournisseur n'a répondu.
     */
    public function analyze(Engagement $engagement): bool
    {
        $analysis = $this->assist->promptJson(
            new EstimateAnalyst,
            $this->describeEngagement($engagement)."\n\n---\n\n".$this->describeGrid(),
            fn (array $data): ?array => self::normalizeAnalysis($data),
        );

        if ($analysis === null) {
            return false;
        }

        $this->saveAnalysis($engagement, $analysis);

        return true;
    }

    /**
     * Enregistre une analyse déjà normalisée (celle de l'IA du site, ou celle d'un agent MCP).
     *
     * @param  array{summary: string, risks: list<string>, unclear_points: list<string>, questions: list<string>, hours_min: int, hours_max: int, billing: string, reasoning: ?string}  $analysis
     */
    public function saveAnalysis(Engagement $engagement, array $analysis): void
    {
        $rate = $engagement->estimate['hourly_rate'] ?? null;

        $engagement->forceFill([
            'ai_analysis' => [
                ...$analysis,
                // Le prix suggéré au taux de la demande, pour comparer avec la fourchette affichée.
                'price_min' => $rate === null ? null : $analysis['hours_min'] * $rate,
                'price_max' => $rate === null ? null : $analysis['hours_max'] * $rate,
                'currency' => $engagement->estimate['currency'] ?? null,
            ],
            'ai_analyzed_at' => now(),
        ])->save();
    }

    /**
     * Ramène une analyse aux champs attendus ; null sans résumé ni heures cohérentes.
     *
     * @param  array<string, mixed>  $data
     * @return array{summary: string, risks: list<string>, unclear_points: list<string>, questions: list<string>, hours_min: int, hours_max: int, billing: string, reasoning: ?string}|null
     */
    public static function normalizeAnalysis(array $data): ?array
    {
        $summary = self::text($data['summary'] ?? null, 1000);
        $hoursMin = self::hours($data['hours_min'] ?? null);
        $hoursMax = self::hours($data['hours_max'] ?? null);

        if ($summary === null || $hoursMin === null || $hoursMax === null) {
            return null;
        }

        return [
            'summary' => $summary,
            'risks' => self::list($data['risks'] ?? null),
            'unclear_points' => self::list($data['unclear_points'] ?? null),
            'questions' => self::list($data['questions'] ?? null),
            'hours_min' => min($hoursMin, $hoursMax),
            'hours_max' => max($hoursMin, $hoursMax),
            'billing' => EstimatorBilling::tryFrom((string) ($data['billing'] ?? ''))?->value ?? EstimatorBilling::Fixed->value,
            'reasoning' => self::text($data['reasoning'] ?? null, 2000),
        ];
    }

    /**
     * Propose un élément de la grille. Null si aucun fournisseur n'a répondu.
     *
     * @return array{name: array{fr: string, en: string}, description: array{fr: ?string, en: ?string}, hours_min: int, hours_max: int, reasoning: ?string}|null
     */
    public function suggestItem(EstimatorItemKind $kind, string $name, ?string $notes): ?array
    {
        $label = $kind === EstimatorItemKind::ProjectType ? 'Type de projet' : 'Option';
        $prompt = "{$label} à chiffrer : {$name}".(filled($notes) ? "\nPrécisions du développeur : {$notes}" : '')
            ."\n\n---\n\n".$this->describeGrid();

        return $this->assist->promptJson(new EstimatorItemAdvisor, $prompt, function (array $data): ?array {
            $nameFr = self::text($data['name_fr'] ?? null, 255);
            $nameEn = self::text($data['name_en'] ?? null, 255);
            $hoursMin = self::hours($data['hours_min'] ?? null);
            $hoursMax = self::hours($data['hours_max'] ?? null);

            if ($nameFr === null || $nameEn === null || $hoursMin === null || $hoursMax === null) {
                return null;
            }

            return [
                'name' => ['fr' => $nameFr, 'en' => $nameEn],
                'description' => [
                    'fr' => self::text($data['description_fr'] ?? null, 1000),
                    'en' => self::text($data['description_en'] ?? null, 1000),
                ],
                'hours_min' => min($hoursMin, $hoursMax),
                'hours_max' => max($hoursMin, $hoursMax),
                'reasoning' => self::text($data['reasoning'] ?? null, 2000),
            ];
        });
    }

    /**
     * Le type de projet et les options qui correspondent à la description d'un visiteur,
     * limités aux éléments actifs. Null si l'IA n'a rien donné d'exploitable.
     *
     * @return array{project_type_id: int, feature_ids: list<int>}|null
     */
    public function match(string $description): ?array
    {
        $items = EstimatorItem::query()->active()->get();
        $projectTypeIds = $items->where('kind', EstimatorItemKind::ProjectType)->pluck('id')->all();
        $featureIds = $items->where('kind', EstimatorItemKind::Feature)->pluck('id')->all();

        if ($projectTypeIds === []) {
            return null;
        }

        $grid = $items->map(fn (EstimatorItem $item): string => sprintf(
            '- [%s] id %d : %s%s',
            $item->kind === EstimatorItemKind::ProjectType ? 'type de projet' : 'option',
            $item->id,
            $item->getTranslation('name', 'fr'),
            filled($item->getTranslation('description', 'fr')) ? ' — '.$item->getTranslation('description', 'fr') : '',
        ))->implode("\n");

        return $this->assist->promptJson(
            new EstimateMatcher,
            "Grille :\n{$grid}\n\nDescription du visiteur :\n\"\"\"\n{$description}\n\"\"\"",
            function (array $data) use ($projectTypeIds, $featureIds): ?array {
                $projectTypeId = filter_var($data['project_type_id'] ?? null, FILTER_VALIDATE_INT);

                if (! in_array($projectTypeId, $projectTypeIds, true)) {
                    return null;
                }

                $features = array_values(array_unique(array_filter(
                    array_map(fn (mixed $id): int|false => filter_var($id, FILTER_VALIDATE_INT), (array) ($data['feature_ids'] ?? [])),
                    fn (int|false $id): bool => in_array($id, $featureIds, true),
                )));

                return ['project_type_id' => $projectTypeId, 'feature_ids' => $features];
            },
            'ai.text_assist',
        );
    }

    /** La grille et les réglages, en texte, pour donner à l'IA les mêmes repères que le site. */
    public function describeGrid(): string
    {
        $settings = SiteSetting::current();
        $rates = collect($settings->estimatorRates())->map(fn (int $rate, string $currency): string => "{$rate} {$currency}/h")->implode(', ');

        $lines = EstimatorItem::query()->active()->get()->map(fn (EstimatorItem $item): string => sprintf(
            '- %s « %s » : %d à %d h%s',
            $item->kind === EstimatorItemKind::ProjectType ? 'Type de projet' : 'Option',
            $item->getTranslation('name', 'fr'),
            $item->hours_min,
            $item->hours_max,
            $item->kind === EstimatorItemKind::ProjectType ? ' ('.($item->billing === EstimatorBilling::Hourly ? 'à l\'heure' : 'forfait').')' : '',
        ));

        return "Grille de l'estimateur (heures hors marge) :\n".($lines->isEmpty() ? '(vide)' : $lines->implode("\n"))
            ."\nTaux horaires : ".($rates !== '' ? $rates : 'non renseignés')
            ."\nMarge pour imprévus : {$settings->estimator_risk_percent} %"
            ."\nHeures facturables par semaine : {$settings->estimator_hours_per_week}";
    }

    private function describeEngagement(Engagement $engagement): string
    {
        $estimate = $engagement->estimate ?? [];

        return implode("\n", array_filter([
            'Demande reçue :',
            'Type de projet : '.($estimate['project_type'] ?? $engagement->subject ?? 'non précisé'),
            'Options : '.(($estimate['features'] ?? []) === [] ? 'aucune' : implode(', ', $estimate['features'])),
            $engagement->estimate_label === null ? null : 'Estimation affichée : '.$engagement->estimate_label,
            $engagement->budget_label === null ? null : 'Budget indiqué : '.$engagement->budget_label,
            $engagement->timeline === null ? null : 'Délai souhaité : '.$engagement->timeline,
            $engagement->company === null ? null : 'Entreprise : '.$engagement->company,
            "Message du visiteur :\n\"\"\"\n".($engagement->message ?: '(aucun message)')."\n\"\"\"",
        ]));
    }

    private static function text(mixed $value, int $max): ?string
    {
        return is_string($value) && trim($value) !== '' ? mb_substr(trim($value), 0, $max) : null;
    }

    private static function hours(mixed $value): ?int
    {
        $hours = filter_var($value, FILTER_VALIDATE_INT);

        return $hours === false || $hours < 0 || $hours > 5000 ? null : $hours;
    }

    /** @return list<string> */
    private static function list(mixed $value): array
    {
        return collect(is_array($value) ? $value : [])
            ->map(fn (mixed $entry): ?string => self::text($entry, 500))
            ->filter()
            ->take(10)
            ->values()
            ->all();
    }
}

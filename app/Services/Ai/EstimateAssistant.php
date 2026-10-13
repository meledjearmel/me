<?php

namespace App\Services\Ai;

use App\Ai\Agents\EstimateAnalyst;
use App\Ai\Agents\EstimateMatcher;
use App\Ai\Agents\EstimatorGridDesigner;
use App\Ai\Agents\EstimatorItemAdvisor;
use App\Enums\EstimatorBilling;
use App\Enums\EstimatorItemKind;
use App\Enums\EstimatorPricing;
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

        return $this->assist->promptJson(new EstimatorItemAdvisor, $prompt, function (array $data) use ($kind): ?array {
            $nameFr = self::text($data['name_fr'] ?? null, 255);
            $nameEn = self::text($data['name_en'] ?? null, 255);
            $pricing = self::pricing($kind, $data);
            $hoursMin = self::hours($data['hours_min'] ?? null) ?? ($pricing === EstimatorPricing::Percent ? 0 : null);
            $hoursMax = self::hours($data['hours_max'] ?? null) ?? ($pricing === EstimatorPricing::Percent ? 0 : null);

            if ($nameFr === null || $nameEn === null || $hoursMin === null || $hoursMax === null) {
                return null;
            }

            return [
                'name' => ['fr' => $nameFr, 'en' => $nameEn],
                'description' => [
                    'fr' => self::text($data['description_fr'] ?? null, 1000),
                    'en' => self::text($data['description_en'] ?? null, 1000),
                ],
                'pricing' => $pricing->value,
                'percent' => $pricing === EstimatorPricing::Percent ? self::percent($data['percent'] ?? null) : null,
                'hours_min' => min($hoursMin, $hoursMax),
                'hours_max' => max($hoursMin, $hoursMax),
                'reasoning' => self::text($data['reasoning'] ?? null, 2000),
            ];
        });
    }

    /**
     * Propose une grille complète d'après ce que le développeur décrit, au format de
     * EstimatorConfigurator (à relire avant de l'appliquer). Null si l'IA n'a rien donné.
     *
     * @return array{settings: array<string, mixed>|null, items: list<array<string, mixed>>, reasoning: ?string}|null
     */
    public function proposeGrid(string $brief): ?array
    {
        $prompt = "Ce que fait le développeur et ses consignes :\n\"\"\"\n{$brief}\n\"\"\"\n\n---\n\n".$this->describeGrid();
        $existingIds = EstimatorItem::query()->pluck('id')->all();

        return $this->assist->promptJson(new EstimatorGridDesigner, $prompt, function (array $data) use ($existingIds): ?array {
            $items = [];
            $refs = [];

            foreach (array_slice((array) ($data['items'] ?? []), 0, 60) as $index => $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $kind = EstimatorItemKind::tryFrom((string) ($entry['kind'] ?? ''));
                $nameFr = self::text($entry['name_fr'] ?? null, 255);
                $nameEn = self::text($entry['name_en'] ?? null, 255);

                if ($kind === null || $nameFr === null || $nameEn === null) {
                    continue;
                }

                $id = filter_var($entry['id'] ?? null, FILTER_VALIDATE_INT);
                $pricing = self::pricing($kind, $entry);
                $hoursMin = self::hours($entry['hours_min'] ?? null) ?? 0;
                $hoursMax = self::hours($entry['hours_max'] ?? null) ?? $hoursMin;
                $ref = self::text($entry['ref'] ?? null, 60) ?? "item-{$index}";

                if ($pricing === EstimatorPricing::Hours && $hoursMax === 0) {
                    continue;
                }

                $items[] = array_filter([
                    'id' => in_array($id, $existingIds, true) ? $id : null,
                    'ref' => isset($refs[$ref]) ? "{$ref}-{$index}" : $ref,
                    'kind' => $kind->value,
                    'name_fr' => $nameFr,
                    'name_en' => $nameEn,
                    'description_fr' => self::text($entry['description_fr'] ?? null, 1000),
                    'description_en' => self::text($entry['description_en'] ?? null, 1000),
                    'hours_min' => min($hoursMin, $hoursMax),
                    'hours_max' => max($hoursMin, $hoursMax),
                    'pricing' => $kind === EstimatorItemKind::Feature ? $pricing->value : null,
                    'percent' => $pricing === EstimatorPricing::Percent ? self::percent($entry['percent'] ?? null) : null,
                    'billing' => $kind === EstimatorItemKind::ProjectType
                        ? (EstimatorBilling::tryFrom((string) ($entry['billing'] ?? ''))?->value ?? EstimatorBilling::Fixed->value)
                        : null,
                    'project_types' => $kind === EstimatorItemKind::Feature ? (array) ($entry['project_types'] ?? []) : null,
                ], fn (mixed $value): bool => $value !== null);
                $refs[$ref] = true;
            }

            if ($items === []) {
                return null;
            }

            // Les liens ne gardent que des types de projet de la proposition, ou existants.
            $projectTypeRefs = collect($items)->where('kind', EstimatorItemKind::ProjectType->value)->pluck('ref')->all();
            $existingTypes = EstimatorItem::query()->where('kind', EstimatorItemKind::ProjectType)->pluck('id')->all();

            foreach ($items as &$item) {
                if (isset($item['project_types'])) {
                    $item['project_types'] = collect($item['project_types'])
                        ->map(fn (mixed $target): int|string|null => match (true) {
                            is_int($target) && in_array($target, $existingTypes, true) => $target,
                            is_string($target) && in_array($target, $projectTypeRefs, true) => $target,
                            default => null,
                        })
                        ->filter(fn (int|string|null $target): bool => $target !== null)
                        ->unique()
                        ->values()
                        ->all();
                }
            }
            unset($item);

            return [
                'settings' => self::settings($data['settings'] ?? null),
                'items' => $items,
                'reasoning' => self::text($data['reasoning'] ?? null, 2000),
            ];
        });
    }

    /** @param  array<string, mixed>  $data */
    private static function pricing(EstimatorItemKind $kind, array $data): EstimatorPricing
    {
        $pricing = EstimatorPricing::tryFrom((string) ($data['pricing'] ?? '')) ?? EstimatorPricing::Hours;

        return $kind === EstimatorItemKind::Feature && $pricing === EstimatorPricing::Percent && self::percent($data['percent'] ?? null) !== null
            ? EstimatorPricing::Percent
            : EstimatorPricing::Hours;
    }

    private static function percent(mixed $value): ?int
    {
        $percent = filter_var($value, FILTER_VALIDATE_INT);

        return $percent === false || $percent < 1 || $percent > 500 ? null : $percent;
    }

    /** @return array<string, mixed>|null */
    private static function settings(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $rates = collect(is_array($value['hourly_rates'] ?? null) ? $value['hourly_rates'] : [])
            ->only(EstimatorItem::CURRENCIES)
            ->map(fn (mixed $rate): int|false => filter_var($rate, FILTER_VALIDATE_INT))
            ->filter(fn (int|false $rate): bool => $rate !== false && $rate > 0)
            ->all();
        $risk = filter_var($value['risk_percent'] ?? null, FILTER_VALIDATE_INT);
        $hoursPerWeek = filter_var($value['hours_per_week'] ?? null, FILTER_VALIDATE_INT);

        $settings = array_filter([
            'hourly_rates' => $rates === [] ? null : $rates,
            'risk_percent' => $risk !== false && $risk >= 0 && $risk <= 200 ? $risk : null,
            'hours_per_week' => $hoursPerWeek !== false && $hoursPerWeek >= 1 && $hoursPerWeek <= 80 ? $hoursPerWeek : null,
        ], fn (mixed $setting): bool => $setting !== null);

        return $settings === [] ? null : $settings;
    }

    /**
     * Le type de projet et les options qui correspondent à la description d'un visiteur,
     * limités aux éléments actifs. Null si l'IA n'a rien donné d'exploitable.
     *
     * @return array{project_type_id: int, feature_ids: list<int>}|null
     */
    public function match(string $description): ?array
    {
        $items = EstimatorItem::query()->active()->with('projectTypes:id')->get();
        $projectTypeIds = $items->where('kind', EstimatorItemKind::ProjectType)->pluck('id')->all();
        $features = $items->where('kind', EstimatorItemKind::Feature)->keyBy('id');

        if ($projectTypeIds === []) {
            return null;
        }

        $grid = $items->map(fn (EstimatorItem $item): string => sprintf(
            '- [%s] id %d : %s%s%s',
            $item->kind === EstimatorItemKind::ProjectType ? 'type de projet' : 'option',
            $item->id,
            $item->getTranslation('name', 'fr'),
            filled($item->getTranslation('description', 'fr')) ? ' — '.$item->getTranslation('description', 'fr') : '',
            $item->projectTypes->isEmpty() ? '' : ' (seulement pour les types '.implode(', ', $item->projectTypes->modelKeys()).')',
        ))->implode("\n");

        return $this->assist->promptJson(
            new EstimateMatcher,
            "Grille :\n{$grid}\n\nDescription du visiteur :\n\"\"\"\n{$description}\n\"\"\"",
            function (array $data) use ($projectTypeIds, $features): ?array {
                $projectTypeId = filter_var($data['project_type_id'] ?? null, FILTER_VALIDATE_INT);

                if (! in_array($projectTypeId, $projectTypeIds, true)) {
                    return null;
                }

                // Seules les options proposées pour ce type de projet sont gardées.
                $chosen = collect((array) ($data['feature_ids'] ?? []))
                    ->map(fn (mixed $id): int|false => filter_var($id, FILTER_VALIDATE_INT))
                    ->filter(fn (int|false $id): bool => $id !== false && $features->has($id) && $features[$id]->isAvailableFor($projectTypeId))
                    ->unique()
                    ->values()
                    ->all();

                return ['project_type_id' => $projectTypeId, 'feature_ids' => $chosen];
            },
            'ai.text_assist',
        );
    }

    /** La grille et les réglages, en texte, pour donner à l'IA les mêmes repères que le site. */
    public function describeGrid(): string
    {
        $settings = SiteSetting::current();
        $rates = collect($settings->estimatorRates())->map(fn (int $rate, string $currency): string => "{$rate} {$currency}/h")->implode(', ');

        $items = EstimatorItem::query()->active()->with('projectTypes')->get();

        $lines = $items->map(fn (EstimatorItem $item): string => sprintf(
            '- %s #%d « %s » : %s%s',
            $item->kind === EstimatorItemKind::ProjectType ? 'Type de projet' : 'Option',
            $item->id,
            $item->getTranslation('name', 'fr'),
            $item->pricing === EstimatorPricing::Percent ? "+{$item->percent} % des heures du projet" : "{$item->hours_min} à {$item->hours_max} h",
            match (true) {
                $item->kind === EstimatorItemKind::ProjectType => ' ('.($item->billing === EstimatorBilling::Hourly ? 'à l\'heure' : 'forfait').')',
                $item->projectTypes->isNotEmpty() => ' (seulement pour : '.$item->projectTypes->map(fn (EstimatorItem $type): string => $type->getTranslation('name', 'fr'))->implode(', ').')',
                default => ' (pour tous les types)',
            },
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

<?php

namespace App\Services;

use App\Enums\EstimatorBilling;
use App\Enums\EstimatorItemKind;
use App\Enums\EstimatorPricing;
use App\Enums\ServicePriceMode;
use App\Models\EstimatorItem;
use App\Models\Service;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;

/**
 * Calcule l'estimation d'un projet à partir des heures : le type de projet donne la base,
 * chaque option s'y ajoute (en heures, ou en pourcentage des heures du projet), puis la
 * marge de risque couvre les imprévus (une estimation
 * trop basse coûte cher à celui qui la donne). Le prix vaut heures × taux horaire de la
 * devise, ligne par ligne (le taux de chaque élément : le sien, sa grille ou le taux global),
 * arrondi pour rester lisible ; le délai découle des heures facturables par semaine.
 */
class ProjectEstimator
{
    /** Pas d'arrondi des montants, par devise. */
    private const ROUNDING = ['CHF' => 50, 'EUR' => 50, 'USD' => 50, 'XOF' => 25000];

    /** Pays qui paient en francs suisses. */
    private const CHF_COUNTRIES = ['CH', 'LI'];

    /** Pays de l'UEMOA, qui paient en francs CFA (XOF). */
    private const XOF_COUNTRIES = ['BJ', 'BF', 'CI', 'GW', 'ML', 'NE', 'SN', 'TG'];

    /** Zone euro, micro-États qui utilisent l'euro, et voisins européens où il est l'usage. */
    private const EUR_COUNTRIES = [
        'AT', 'BE', 'BG', 'CY', 'DE', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PT', 'SI', 'SK',
        'AD', 'MC', 'SM', 'VA', 'ME', 'XK',
        'GF', 'GP', 'MQ', 'RE', 'YT', 'PM', 'BL', 'MF',
    ];

    /**
     * La devise proposée d'office à un visiteur : celle de son pays si elle a un taux, sinon
     * celle de la langue (franc suisse en français, euro en anglais), sinon la première.
     * Hors de ces zones, le dollar américain. Le visiteur peut toujours en changer.
     */
    public function defaultCurrency(?string $countryCode, string $locale): ?string
    {
        $available = $this->currencies();
        $country = strtoupper((string) $countryCode);

        $fromCountry = match (true) {
            $country === '' => null,
            in_array($country, self::CHF_COUNTRIES, true) => 'CHF',
            in_array($country, self::XOF_COUNTRIES, true) => 'XOF',
            in_array($country, self::EUR_COUNTRIES, true) => 'EUR',
            default => 'USD',
        };

        foreach ([$fromCountry, $locale === 'en' ? 'EUR' : 'CHF'] as $candidate) {
            if ($candidate !== null && in_array($candidate, $available, true)) {
                return $candidate;
            }
        }

        return $available[0] ?? null;
    }

    /** L'estimateur est proposé avec au moins un type de projet actif et un taux horaire. */
    public function isAvailable(): bool
    {
        return $this->currencies() !== []
            && EstimatorItem::query()->active()->where('kind', EstimatorItemKind::ProjectType)->exists();
    }

    /**
     * Devises proposées : celles qui ont un taux horaire.
     *
     * @return list<string>
     */
    public function currencies(): array
    {
        return array_keys(SiteSetting::current()->estimatorRates());
    }

    /**
     * Les options récurrentes (maintenance, hébergement…) restent hors du total, du délai et des
     * pourcentages : elles forment le bloc « recurring », facturé à la période choisie.
     *
     * @param  Collection<int, EstimatorItem>  $features
     * @param  array<int|string, string>  $periods  Période choisie par option récurrente (id => période).
     * @return array{billing: string, currency: string, hourly_rate: int, risk_percent: int, project_type: string, rate_grid: string|null, features: list<string>, hours_min: int, hours_max: int, price_min: int, price_max: int, weeks_min: int, weeks_max: int, lines: list<array{label: string, hours_min: int, hours_max: int, rate: int}>, recurring: list<array{id: int, label: string, period: string, months: int, discount_percent: int, hours_min: int, hours_max: int, rate: int, price_min: int, price_max: int}>}
     */
    public function estimate(EstimatorItem $projectType, Collection $features, string $currency, array $periods = []): array
    {
        $settings = SiteSetting::current();
        $globalRates = $settings->estimatorRates();
        $risk = max(0, (int) $settings->estimator_risk_percent);
        $hoursPerWeek = max(1, (int) $settings->estimator_hours_per_week);
        [$recurring, $features] = $features->partition(fn (EstimatorItem $item): bool => $item->pricing === EstimatorPricing::Recurring);
        // Les options en heures s'ajoutent à la base ; celles en pourcentage s'appliquent
        // ensuite à ce total, puis la marge pour imprévus couvre le tout.
        $items = collect([$projectType])->merge($features->where('pricing', '!==', EstimatorPricing::Percent));
        $items->each(fn (EstimatorItem $item) => $item->loadMissing('rate'));
        $percentShare = (int) $features->where('pricing', EstimatorPricing::Percent)->sum('percent') / 100;
        $riskMultiplier = 1 + $risk / 100;
        $rate = $projectType->hourlyRate($currency, $globalRates);

        $hoursMin = (int) ceil($items->sum('hours_min') * (1 + $percentShare) * $riskMultiplier);
        $hoursMax = max($hoursMin, (int) ceil($items->sum('hours_max') * (1 + $percentShare) * $riskMultiplier));

        // Chaque ligne au taux de son élément ; les heures des options en pourcentage au
        // taux du type de projet.
        $price = fn (string $bound): float => (
            $items->sum(fn (EstimatorItem $item): int => $item->{$bound} * $item->hourlyRate($currency, $globalRates))
            + $items->sum($bound) * $percentShare * $rate
        ) * $riskMultiplier;
        $priceMin = $this->round((int) ceil($price('hours_min')), $currency);
        $priceMax = max($priceMin, $this->round((int) ceil($price('hours_max')), $currency));

        return [
            'billing' => ($projectType->billing ?? EstimatorBilling::Fixed)->value,
            'currency' => $currency,
            'hourly_rate' => $rate,
            'risk_percent' => $risk,
            'project_type' => $projectType->getTranslation('name', 'fr'),
            // Grille tarifaire du type de projet (null : taux propres ou taux global), pour les statistiques.
            'rate_grid' => $projectType->rate?->getTranslation('name', 'fr'),
            'features' => $features->merge($recurring)->map(fn (EstimatorItem $item): string => $item->getTranslation('name', 'fr'))->values()->all(),
            'hours_min' => $hoursMin,
            'hours_max' => $hoursMax,
            'price_min' => $priceMin,
            'price_max' => $priceMax,
            'weeks_min' => max(1, (int) ceil($hoursMin / $hoursPerWeek)),
            'weeks_max' => max(1, (int) ceil($hoursMax / $hoursPerWeek)),
            'lines' => $this->lines($items, $features->where('pricing', EstimatorPricing::Percent), $currency, $globalRates, $riskMultiplier, $rate),
            'recurring' => $this->recurring($recurring, $periods, $currency, $globalRates),
        ];
    }

    /**
     * Les options récurrentes, au prix de la période choisie : heures par mois × taux × nombre
     * de mois, moins la remise de la période. Sans marge pour imprévus : c'est un engagement fixe.
     *
     * @param  Collection<int, EstimatorItem>  $recurring
     * @param  array<int|string, string>  $periods
     * @param  array<string, int>  $globalRates
     * @return list<array{id: int, label: string, period: string, months: int, discount_percent: int, hours_min: int, hours_max: int, rate: int, price_min: int, price_max: int}>
     */
    private function recurring(Collection $recurring, array $periods, string $currency, array $globalRates): array
    {
        return $recurring->map(function (EstimatorItem $item) use ($periods, $currency, $globalRates): array {
            $item->loadMissing('rate');
            $period = $item->periodFor($periods[$item->id] ?? null);
            $discount = EstimatorItem::filterPeriods($item->periods)[$period->value] ?? 0;
            $rate = $item->hourlyRate($currency, $globalRates);
            $price = fn (int $hours): int => $this->round((int) ceil($hours * $rate * $period->months() * (1 - $discount / 100)), $currency);
            $priceMin = $price($item->hours_min);

            return [
                'id' => $item->id,
                'label' => $item->getTranslation('name', 'fr'),
                'period' => $period->value,
                'months' => $period->months(),
                'discount_percent' => $discount,
                'hours_min' => $item->hours_min,
                'hours_max' => $item->hours_max,
                'rate' => $rate,
                'price_min' => $priceMin,
                'price_max' => max($priceMin, $price($item->hours_max)),
            ];
        })->values()->all();
    }

    /**
     * Le détail de l'estimation, ligne par ligne, marge comprise : de quoi préremplir un devis.
     * Une option en pourcentage devient une ligne d'heures au taux du type de projet.
     *
     * @param  Collection<int, EstimatorItem>  $items
     * @param  Collection<int, EstimatorItem>  $percentFeatures
     * @param  array<string, int>  $globalRates
     * @return list<array{label: string, hours_min: int, hours_max: int, rate: int}>
     */
    private function lines(Collection $items, Collection $percentFeatures, string $currency, array $globalRates, float $riskMultiplier, int $projectRate): array
    {
        $lines = $items->map(fn (EstimatorItem $item): array => [
            'label' => $item->getTranslation('name', 'fr'),
            'hours_min' => (int) ceil($item->hours_min * $riskMultiplier),
            'hours_max' => (int) ceil($item->hours_max * $riskMultiplier),
            'rate' => $item->hourlyRate($currency, $globalRates),
        ]);

        $percentLines = $percentFeatures->map(fn (EstimatorItem $item): array => [
            'label' => $item->getTranslation('name', 'fr'),
            'hours_min' => (int) ceil($items->sum('hours_min') * $item->percent / 100 * $riskMultiplier),
            'hours_max' => (int) ceil($items->sum('hours_max') * $item->percent / 100 * $riskMultiplier),
            'rate' => $projectRate,
        ]);

        return $lines->merge($percentLines)->values()->all();
    }

    /**
     * Le prix « à partir de » d'une offre, dans la devise demandée : le bas de la fourchette
     * de son type de projet (sans option) si le prix vient de l'estimateur (son taux horaire
     * pour un type facturé à l'heure), ou le montant
     * saisi (dans sa devise s'il n'existe pas dans celle du visiteur). Null : « sur devis ».
     *
     * @return array{amount: int, currency: string, billing: string|null}|null
     */
    public function startingPrice(Service $service, string $currency): ?array
    {
        if ($service->price_mode === ServicePriceMode::Manual) {
            $prices = array_filter((array) $service->manual_prices, fn (mixed $amount): bool => (int) $amount > 0);
            $chosen = isset($prices[$currency]) ? $currency : array_key_first($prices);

            return $chosen === null ? null : ['amount' => (int) $prices[$chosen], 'currency' => $chosen, 'billing' => null];
        }

        $projectType = $service->estimatorItem;

        if ($service->price_mode !== ServicePriceMode::Estimator || $projectType === null || ! in_array($currency, $this->currencies(), true)) {
            return null;
        }

        $estimate = $this->estimate($projectType, collect(), $currency);

        // Facturé à l'heure : c'est le taux qui parle au visiteur, pas un total.
        return $estimate['billing'] === EstimatorBilling::Hourly->value
            ? ['amount' => $estimate['hourly_rate'], 'currency' => $currency, 'billing' => $estimate['billing']]
            : ['amount' => $estimate['price_min'], 'currency' => $currency, 'billing' => $estimate['billing']];
    }

    private function round(int $amount, string $currency): int
    {
        $step = self::ROUNDING[$currency] ?? 1;

        return (int) (round($amount / $step) * $step);
    }
}

<?php

namespace App\Services;

use App\Enums\EstimatorBilling;
use App\Enums\EstimatorItemKind;
use App\Models\EstimatorItem;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;

/**
 * Calcule l'estimation d'un projet à partir des heures : le type de projet donne la base,
 * chaque option s'y ajoute, puis la marge de risque couvre les imprévus (une estimation
 * trop basse coûte cher à celui qui la donne). Le prix vaut heures × taux horaire de la
 * devise, arrondi pour rester lisible ; le délai découle des heures facturables par semaine.
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
     * @param  Collection<int, EstimatorItem>  $features
     * @return array{billing: string, currency: string, hourly_rate: int, risk_percent: int, project_type: string, features: list<string>, hours_min: int, hours_max: int, price_min: int, price_max: int, weeks_min: int, weeks_max: int}
     */
    public function estimate(EstimatorItem $projectType, Collection $features, string $currency): array
    {
        $settings = SiteSetting::current();
        $rate = $settings->estimatorRates()[$currency] ?? 0;
        $risk = max(0, (int) $settings->estimator_risk_percent);
        $hoursPerWeek = max(1, (int) $settings->estimator_hours_per_week);
        $items = collect([$projectType])->merge($features);

        $hoursMin = (int) ceil($items->sum('hours_min') * (1 + $risk / 100));
        $hoursMax = max($hoursMin, (int) ceil($items->sum('hours_max') * (1 + $risk / 100)));

        return [
            'billing' => ($projectType->billing ?? EstimatorBilling::Fixed)->value,
            'currency' => $currency,
            'hourly_rate' => $rate,
            'risk_percent' => $risk,
            'project_type' => $projectType->getTranslation('name', 'fr'),
            'features' => $features->map(fn (EstimatorItem $item): string => $item->getTranslation('name', 'fr'))->values()->all(),
            'hours_min' => $hoursMin,
            'hours_max' => $hoursMax,
            'price_min' => $this->round($hoursMin * $rate, $currency),
            'price_max' => $this->round($hoursMax * $rate, $currency),
            'weeks_min' => max(1, (int) ceil($hoursMin / $hoursPerWeek)),
            'weeks_max' => max(1, (int) ceil($hoursMax / $hoursPerWeek)),
        ];
    }

    private function round(int $amount, string $currency): int
    {
        $step = self::ROUNDING[$currency] ?? 1;

        return (int) (round($amount / $step) * $step);
    }
}

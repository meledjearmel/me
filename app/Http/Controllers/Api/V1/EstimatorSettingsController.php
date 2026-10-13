<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EstimatorSettingsRequest;
use App\Models\EstimatorItem;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;

/**
 * @tags Estimateur de projet
 */
class EstimatorSettingsController extends Controller
{
    /**
     * Réglages de l'estimateur
     *
     * Taux horaire par devise (une devise sans taux n'est pas proposée au visiteur), marge
     * pour imprévus ajoutée aux heures, heures facturables par semaine pour le délai.
     *
     * @response array{estimator_hourly_rates: array<string, int|null>, estimator_risk_percent: int, estimator_hours_per_week: int}
     */
    public function show(): JsonResponse
    {
        return response()->json($this->settings());
    }

    /**
     * Modifier les réglages de l'estimateur
     *
     * @response array{estimator_hourly_rates: array<string, int|null>, estimator_risk_percent: int, estimator_hours_per_week: int}
     */
    public function update(EstimatorSettingsRequest $request): JsonResponse
    {
        SiteSetting::current()->update([
            ...$request->validated(),
            'estimator_hourly_rates' => array_filter($request->validated('estimator_hourly_rates'), fn (mixed $rate): bool => (int) $rate > 0),
        ]);

        return response()->json($this->settings());
    }

    /** @return array{estimator_hourly_rates: array<string, int|null>, estimator_risk_percent: int, estimator_hours_per_week: int} */
    private function settings(): array
    {
        $settings = SiteSetting::current();

        return [
            'estimator_hourly_rates' => collect(EstimatorItem::CURRENCIES)
                ->mapWithKeys(fn (string $currency): array => [$currency => $settings->estimatorRates()[$currency] ?? null])
                ->all(),
            'estimator_risk_percent' => $settings->estimator_risk_percent,
            'estimator_hours_per_week' => $settings->estimator_hours_per_week,
        ];
    }
}

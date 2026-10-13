<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EstimatorSettingsRequest;
use App\Models\EstimatorItem;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Les tarifs de l'estimateur de projet : taux horaire par devise, marge de risque, heures par semaine. */
class EstimatorSettingsController extends Controller
{
    public function edit(): Response
    {
        $settings = SiteSetting::current();

        return Inertia::render('admin/estimator-settings/edit', [
            'settings' => [
                'estimator_hourly_rates' => collect(EstimatorItem::CURRENCIES)
                    ->mapWithKeys(fn (string $currency): array => [$currency => $settings->estimatorRates()[$currency] ?? null])
                    ->all(),
                'estimator_risk_percent' => $settings->estimator_risk_percent,
                'estimator_hours_per_week' => $settings->estimator_hours_per_week,
            ],
        ]);
    }

    public function update(EstimatorSettingsRequest $request): RedirectResponse
    {
        SiteSetting::current()->update([
            ...$request->validated(),
            'estimator_hourly_rates' => array_filter($request->validated('estimator_hourly_rates'), fn (mixed $rate): bool => (int) $rate > 0),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tarifs de l\'estimateur enregistrés.')]);

        return back();
    }
}

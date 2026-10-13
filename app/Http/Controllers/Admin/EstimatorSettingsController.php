<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EstimatorSettingsRequest;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/** Les réglages de l'estimateur de projet : taux horaire par devise, marge de risque, heures par semaine. */
class EstimatorSettingsController extends Controller
{
    public function update(EstimatorSettingsRequest $request): RedirectResponse
    {
        SiteSetting::current()->update([
            ...$request->validated(),
            'estimator_hourly_rates' => array_filter($request->validated('estimator_hourly_rates'), fn (mixed $rate): bool => (int) $rate > 0),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Réglages de l\'estimateur enregistrés.')]);

        return back();
    }
}

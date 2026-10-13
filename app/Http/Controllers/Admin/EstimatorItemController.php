<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EstimatorItemRequest;
use App\Models\EstimatorItem;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** La grille de l'estimateur de projet : types de projet et options, avec leurs prix et délais. */
class EstimatorItemController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/estimator-items/index', [
            'estimatorItems' => $this->paginateList(EstimatorItem::query()->orderBy('kind', 'desc')->orderBy('sort_order'), $request, ['name->fr', 'name->en']),
            'filters' => $this->listFilters($request),
            'settings' => $this->settings(),
        ]);
    }

    /**
     * Taux horaires, marge et rythme, partagés par toute la grille.
     *
     * @return array{estimator_hourly_rates: array<string, int|null>, estimator_risk_percent: int, estimator_hours_per_week: int}
     */
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

    public function create(): Response
    {
        return Inertia::render('admin/estimator-items/create');
    }

    public function store(EstimatorItemRequest $request): RedirectResponse
    {
        EstimatorItem::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Élément de l\'estimateur créé.')]);

        return to_route('admin.estimator-items.index');
    }

    public function edit(EstimatorItem $estimatorItem): Response
    {
        return Inertia::render('admin/estimator-items/edit', [
            'estimatorItem' => $estimatorItem,
        ]);
    }

    public function update(EstimatorItemRequest $request, EstimatorItem $estimatorItem): RedirectResponse
    {
        $estimatorItem->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Élément de l\'estimateur mis à jour.')]);

        return to_route('admin.estimator-items.index');
    }

    public function destroy(EstimatorItem $estimatorItem): RedirectResponse
    {
        $estimatorItem->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Élément de l\'estimateur supprimé.')]);

        return to_route('admin.estimator-items.index');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EstimatorRateRequest;
use App\Models\EstimatorRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Les grilles tarifaires de l'estimateur : des taux horaires nommés que partagent les types
 * de projet et options qui y sont rattachés. Supprimer une grille rend à ses éléments le
 * taux global (ou leurs propres taux).
 */
class EstimatorRateController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/estimator-rates/index', [
            'rates' => $this->paginateList(EstimatorRate::query()->withCount('items')->orderBy('sort_order'), $request, ['name->fr', 'name->en']),
            'filters' => $this->listFilters($request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/estimator-rates/create');
    }

    public function store(EstimatorRateRequest $request): RedirectResponse
    {
        EstimatorRate::query()->create($request->rateData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Grille tarifaire créée.')]);

        return to_route('admin.estimator-rates.index');
    }

    public function edit(EstimatorRate $estimatorRate): Response
    {
        return Inertia::render('admin/estimator-rates/edit', [
            'rate' => $estimatorRate->loadCount('items'),
        ]);
    }

    public function update(EstimatorRateRequest $request, EstimatorRate $estimatorRate): RedirectResponse
    {
        $estimatorRate->update($request->rateData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Grille tarifaire mise à jour.')]);

        return to_route('admin.estimator-rates.index');
    }

    public function destroy(EstimatorRate $estimatorRate): RedirectResponse
    {
        $estimatorRate->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Grille tarifaire supprimée.')]);

        return to_route('admin.estimator-rates.index');
    }
}

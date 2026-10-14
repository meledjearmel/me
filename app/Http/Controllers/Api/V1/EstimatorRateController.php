<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EstimatorRateRequest;
use App\Http\Resources\Api\V1\EstimatorRateResource;
use App\Models\EstimatorRate;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Estimateur de projet
 */
class EstimatorRateController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des grilles tarifaires
     *
     * Des taux horaires nommés, par devise. Un élément de l'estimateur suit ses propres taux
     * (`hourly_rates`), sinon sa grille (`estimator_rate_id`), sinon le taux global des réglages.
     */
    #[QueryParameter('search', description: 'Recherche dans le nom.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return EstimatorRateResource::collection(
            $this->paginateList(EstimatorRate::query()->withCount('items')->orderBy('sort_order'), $request, ['name->fr', 'name->en'])
        );
    }

    /**
     * Créer une grille tarifaire
     *
     * Une devise vide ou à 0 suit le taux global des réglages.
     */
    public function store(EstimatorRateRequest $request): EstimatorRateResource
    {
        return new EstimatorRateResource(EstimatorRate::query()->create($request->rateData())->loadCount('items'));
    }

    /**
     * Détail d'une grille tarifaire
     */
    public function show(EstimatorRate $estimatorRate): EstimatorRateResource
    {
        return new EstimatorRateResource($estimatorRate->loadCount('items'));
    }

    /**
     * Modifier une grille tarifaire
     */
    public function update(EstimatorRateRequest $request, EstimatorRate $estimatorRate): EstimatorRateResource
    {
        $estimatorRate->update($request->rateData());

        return new EstimatorRateResource($estimatorRate->loadCount('items'));
    }

    /**
     * Supprimer une grille tarifaire
     *
     * Ses éléments suivent ensuite leurs propres taux ou le taux global.
     */
    public function destroy(EstimatorRate $estimatorRate): Response
    {
        $estimatorRate->delete();

        return response()->noContent();
    }
}

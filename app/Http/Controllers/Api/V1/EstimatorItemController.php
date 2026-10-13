<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EstimatorItemRequest;
use App\Http\Resources\Api\V1\EstimatorItemResource;
use App\Models\EstimatorItem;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Estimateur de projet
 */
class EstimatorItemController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des éléments de l'estimateur
     *
     * Types de projet (la base du prix et du délai) puis options qui s'y ajoutent.
     */
    #[QueryParameter('search', description: 'Recherche dans le nom.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return EstimatorItemResource::collection(
            $this->paginateList(EstimatorItem::query()->orderBy('kind', 'desc')->orderBy('sort_order'), $request, ['name->fr', 'name->en'])
        );
    }

    /**
     * Créer un élément de l'estimateur
     */
    public function store(EstimatorItemRequest $request): EstimatorItemResource
    {
        return new EstimatorItemResource(EstimatorItem::query()->create($request->validated()));
    }

    /**
     * Détail d'un élément de l'estimateur
     */
    public function show(EstimatorItem $estimatorItem): EstimatorItemResource
    {
        return new EstimatorItemResource($estimatorItem);
    }

    /**
     * Modifier un élément de l'estimateur
     */
    public function update(EstimatorItemRequest $request, EstimatorItem $estimatorItem): EstimatorItemResource
    {
        $estimatorItem->update($request->validated());

        return new EstimatorItemResource($estimatorItem);
    }

    /**
     * Supprimer un élément de l'estimateur
     */
    public function destroy(EstimatorItem $estimatorItem): Response
    {
        $estimatorItem->delete();

        return response()->noContent();
    }
}

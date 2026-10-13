<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Enums\EstimatorItemKind;
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
            $this->paginateList(EstimatorItem::query()->with('projectTypes:id')->orderBy('kind', 'desc')->orderBy('sort_order'), $request, ['name->fr', 'name->en'])
        );
    }

    /**
     * Créer un élément de l'estimateur
     *
     * Une option peut ajouter des heures (`pricing` = `hours`) ou un pourcentage des heures
     * du projet (`percent`), et être réservée à certains types de projet (`project_type_ids`).
     * Pour configurer toute la grille d'un coup, voir l'outil MCP `configure-estimator`.
     */
    public function store(EstimatorItemRequest $request): EstimatorItemResource
    {
        $item = EstimatorItem::query()->create($request->safe()->except('project_type_ids'));
        $this->syncProjectTypes($item, $request);

        return new EstimatorItemResource($item->load('projectTypes:id'));
    }

    /**
     * Détail d'un élément de l'estimateur
     */
    public function show(EstimatorItem $estimatorItem): EstimatorItemResource
    {
        return new EstimatorItemResource($estimatorItem->load('projectTypes:id'));
    }

    /**
     * Modifier un élément de l'estimateur
     */
    public function update(EstimatorItemRequest $request, EstimatorItem $estimatorItem): EstimatorItemResource
    {
        $estimatorItem->update($request->safe()->except('project_type_ids'));
        $this->syncProjectTypes($estimatorItem, $request);

        return new EstimatorItemResource($estimatorItem->load('projectTypes:id'));
    }

    private function syncProjectTypes(EstimatorItem $item, EstimatorItemRequest $request): void
    {
        $item->projectTypes()->sync($item->kind === EstimatorItemKind::Feature ? ($request->validated('project_type_ids') ?? []) : []);
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

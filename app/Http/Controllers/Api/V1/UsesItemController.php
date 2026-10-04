<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UsesItemRequest;
use App\Http\Resources\Api\V1\UsesItemResource;
use App\Models\UsesItem;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Uses
 */
class UsesItemController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des éléments « Uses »
     *
     * Par rubrique puis par ordre. Seuls les éléments publiés s'affichent sur la page
     * publique ; sans aucun, la page renvoie une 404.
     */
    #[QueryParameter('search', description: 'Recherche dans le nom et la description.', type: 'string')]
    #[QueryParameter('category', description: 'Rubrique : `hardware`, `development`, `apps` ou `services`.', type: 'string')]
    #[QueryParameter('status', description: 'Statut : `published` ou `draft`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return UsesItemResource::collection(
            $this->paginateList(
                UsesItem::query()->orderBy('category')->orderBy('sort_order'),
                $request,
                ['name', 'description->fr', 'description->en'],
                ['category', 'status'],
            )
        );
    }

    /**
     * Créer un élément « Uses »
     */
    public function store(UsesItemRequest $request): UsesItemResource
    {
        return new UsesItemResource(UsesItem::query()->create($request->validated())->refresh());
    }

    /**
     * Détail d'un élément « Uses »
     */
    public function show(UsesItem $usesItem): UsesItemResource
    {
        return new UsesItemResource($usesItem);
    }

    /**
     * Modifier un élément « Uses »
     */
    public function update(UsesItemRequest $request, UsesItem $usesItem): UsesItemResource
    {
        $usesItem->update($request->validated());

        return new UsesItemResource($usesItem);
    }

    /**
     * Supprimer un élément « Uses »
     *
     * L'élément part dans la corbeille (type `uses-items`).
     */
    public function destroy(UsesItem $usesItem): Response
    {
        $usesItem->delete();

        return response()->noContent();
    }
}

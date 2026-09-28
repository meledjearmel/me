<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TechnologyRequest;
use App\Http\Resources\Api\V1\TechnologyResource;
use App\Models\Technology;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Technologies
 */
class TechnologyController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des technologies
     */
    #[QueryParameter('search', description: 'Recherche dans le nom.', type: 'string')]
    #[QueryParameter('category_id', description: 'Identifiant de la catégorie de technologie.', type: 'integer')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return TechnologyResource::collection(
            $this->paginateList(Technology::query()->with('category')->orderBy('name'), $request, ['name'], ['category_id'])
        );
    }

    /**
     * Créer une technologie
     *
     * `category_id` est l'identifiant d'une catégorie (voir les endpoints des catégories de
     * technologies). `icon` est le `slug` d'un logo de la bibliothèque (voir les endpoints des
     * logos de technologies) ; un logo inconnu est refusé.
     */
    public function store(TechnologyRequest $request): TechnologyResource
    {
        return new TechnologyResource(Technology::query()->create($request->validated()));
    }

    /**
     * Détail d'une technologie
     */
    public function show(Technology $technology): TechnologyResource
    {
        return new TechnologyResource($technology);
    }

    /**
     * Modifier une technologie
     *
     * `icon` est le `slug` d'un logo de la bibliothèque. Un logo déjà enregistré reste accepté
     * tel quel, même sans fichier ; en changer exige un logo existant.
     */
    public function update(TechnologyRequest $request, Technology $technology): TechnologyResource
    {
        $technology->update($request->validated());

        return new TechnologyResource($technology);
    }

    /**
     * Supprimer une technologie
     */
    public function destroy(Technology $technology): Response
    {
        $technology->delete();

        return response()->noContent();
    }
}

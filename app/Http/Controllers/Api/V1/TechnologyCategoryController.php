<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TechnologyCategoryRequest;
use App\Http\Resources\Api\V1\TechnologyCategoryResource;
use App\Models\TechnologyCategory;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Catégories de technologies
 */
class TechnologyCategoryController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des catégories de technologies
     */
    #[QueryParameter('search', description: 'Recherche dans la clé et le libellé.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return TechnologyCategoryResource::collection(
            $this->paginateList(TechnologyCategory::query()->orderBy('sort_order'), $request, ['key', 'label->fr', 'label->en'])
        );
    }

    /**
     * Créer une catégorie de technologies
     */
    public function store(TechnologyCategoryRequest $request): TechnologyCategoryResource
    {
        return new TechnologyCategoryResource(TechnologyCategory::query()->create($request->validated()));
    }

    /**
     * Détail d'une catégorie de technologies
     */
    public function show(TechnologyCategory $technologyCategory): TechnologyCategoryResource
    {
        return new TechnologyCategoryResource($technologyCategory);
    }

    /**
     * Modifier une catégorie de technologies
     */
    public function update(TechnologyCategoryRequest $request, TechnologyCategory $technologyCategory): TechnologyCategoryResource
    {
        $technologyCategory->update($request->validated());

        return new TechnologyCategoryResource($technologyCategory);
    }

    /**
     * Supprimer une catégorie de technologies
     *
     * Supprime aussi (en cascade) les technologies qui lui sont rattachées.
     */
    public function destroy(TechnologyCategory $technologyCategory): Response
    {
        $technologyCategory->delete();

        return response()->noContent();
    }
}

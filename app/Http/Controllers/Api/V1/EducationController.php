<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EducationRequest;
use App\Http\Resources\Api\V1\EducationResource;
use App\Models\Education;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Formations
 */
class EducationController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des formations
     */
    #[QueryParameter('search', description: 'Recherche dans l’établissement et le diplôme.', type: 'string')]
    #[QueryParameter('status', description: 'Statut de publication : `draft` ou `published`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return EducationResource::collection(
            $this->paginateList(Education::query()->orderBy('sort_order'), $request, ['institution', 'degree->fr', 'degree->en'], ['status'])
        );
    }

    /**
     * Créer une formation
     */
    public function store(EducationRequest $request): EducationResource
    {
        return new EducationResource(Education::query()->create($request->validated()));
    }

    /**
     * Détail d'une formation
     */
    public function show(Education $education): EducationResource
    {
        return new EducationResource($education);
    }

    /**
     * Modifier une formation
     */
    public function update(EducationRequest $request, Education $education): EducationResource
    {
        $education->update($request->validated());

        return new EducationResource($education);
    }

    /**
     * Supprimer une formation
     */
    public function destroy(Education $education): Response
    {
        $education->delete();

        return response()->noContent();
    }
}

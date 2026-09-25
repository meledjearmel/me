<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProfessionalReferenceRequest;
use App\Http\Resources\Api\V1\ProfessionalReferenceResource;
use App\Models\ProfessionalReference;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Références professionnelles
 */
class ProfessionalReferenceController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des références
     */
    #[QueryParameter('search', description: 'Recherche dans le nom, le rôle, la société et l’e-mail.', type: 'string')]
    #[QueryParameter('is_public', description: 'Références publiques : `1` ou `0`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return ProfessionalReferenceResource::collection(
            $this->paginateList(ProfessionalReference::query()->with('project')->latest(), $request, ['name', 'role', 'company', 'email'], ['is_public'])
        );
    }

    /**
     * Créer une référence
     */
    public function store(ProfessionalReferenceRequest $request): ProfessionalReferenceResource
    {
        $reference = ProfessionalReference::query()->create($request->validated());

        return new ProfessionalReferenceResource($reference->load('project'));
    }

    /**
     * Détail d'une référence
     */
    public function show(ProfessionalReference $professionalReference): ProfessionalReferenceResource
    {
        return new ProfessionalReferenceResource($professionalReference->load('project'));
    }

    /**
     * Modifier une référence
     */
    public function update(ProfessionalReferenceRequest $request, ProfessionalReference $professionalReference): ProfessionalReferenceResource
    {
        $professionalReference->update($request->validated());

        return new ProfessionalReferenceResource($professionalReference->load('project'));
    }

    /**
     * Supprimer une référence
     */
    public function destroy(ProfessionalReference $professionalReference): Response
    {
        $professionalReference->delete();

        return response()->noContent();
    }
}

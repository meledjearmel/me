<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\EngagementRequest;
use App\Http\Resources\Api\V1\EngagementResource;
use App\Models\Engagement;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Demandes de collaboration
 */
class EngagementController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des demandes
     */
    #[QueryParameter('search', description: 'Recherche dans le nom, l’e-mail, la société et le sujet.', type: 'string')]
    #[QueryParameter('type', description: 'Type : `freelance` ou `hiring`.', type: 'string')]
    #[QueryParameter('status', description: 'Statut : `new` ou `handled`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return EngagementResource::collection(
            $this->paginateList(Engagement::query()->with('jobProfile')->latest(), $request, ['name', 'email', 'company', 'subject'], ['type', 'status'])
        );
    }

    /**
     * Détail d'une demande
     */
    public function show(Engagement $engagement): EngagementResource
    {
        return new EngagementResource($engagement->load('jobProfile'));
    }

    /**
     * Marquer une demande comme traitée (ou « nouvelle »)
     */
    public function update(EngagementRequest $request, Engagement $engagement): EngagementResource
    {
        $engagement->update($request->validated());

        return new EngagementResource($engagement->load('jobProfile'));
    }

    /**
     * Supprimer une demande
     */
    public function destroy(Engagement $engagement): Response
    {
        $engagement->delete();

        return response()->noContent();
    }
}

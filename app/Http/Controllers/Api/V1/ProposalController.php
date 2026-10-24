<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProposalRequest;
use App\Http\Resources\Api\V1\ProposalResource;
use App\Models\Proposal;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Propositions freelance
 */
class ProposalController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des propositions
     *
     * Propositions envoyées sur les plateformes freelance (Upwork, Malt…), privées : elles servent
     * d'historique et d'exemples à l'IA qui rédige les suivantes.
     */
    #[QueryParameter('search', description: 'Recherche dans le titre de l\'offre, le client et la proposition.', type: 'string')]
    #[QueryParameter('platform', description: 'Filtre par plateforme.', type: 'string')]
    #[QueryParameter('status', description: 'Filtre par statut : `draft`, `sent`, `viewed`, `interview`, `hired` ou `declined`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return ProposalResource::collection(
            $this->paginateList(Proposal::query()->latest(), $request, ['job_title', 'client_name', 'body'], ['platform', 'status'])
        );
    }

    /**
     * Enregistrer une proposition
     */
    public function store(ProposalRequest $request): ProposalResource
    {
        return new ProposalResource(Proposal::query()->create($request->validated())->refresh());
    }

    /**
     * Détail d'une proposition
     */
    public function show(Proposal $proposal): ProposalResource
    {
        return new ProposalResource($proposal);
    }

    /**
     * Modifier une proposition
     *
     * Pour coller la version réellement envoyée ou suivre le résultat (statut).
     */
    public function update(ProposalRequest $request, Proposal $proposal): ProposalResource
    {
        $proposal->update($request->validated());

        return new ProposalResource($proposal);
    }

    /**
     * Supprimer une proposition
     */
    public function destroy(Proposal $proposal): Response
    {
        $proposal->delete();

        return response()->noContent();
    }
}

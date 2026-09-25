<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DomainRequest;
use App\Http\Resources\Api\V1\DomainResource;
use App\Models\Domain;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Domaines
 */
class DomainController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des domaines
     */
    #[QueryParameter('search', description: 'Recherche dans la clé et le libellé.', type: 'string')]
    #[QueryParameter('status', description: 'Statut de publication : `draft` ou `published`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return DomainResource::collection(
            $this->paginateList(Domain::query()->orderBy('sort_order'), $request, ['key', 'label->fr', 'label->en'], ['status'])
        );
    }

    /**
     * Créer un domaine
     */
    public function store(DomainRequest $request): DomainResource
    {
        return new DomainResource(Domain::query()->create($request->validated()));
    }

    /**
     * Détail d'un domaine
     */
    public function show(Domain $domain): DomainResource
    {
        return new DomainResource($domain);
    }

    /**
     * Modifier un domaine
     */
    public function update(DomainRequest $request, Domain $domain): DomainResource
    {
        $domain->update($request->validated());

        return new DomainResource($domain);
    }

    /**
     * Supprimer un domaine
     */
    public function destroy(Domain $domain): Response
    {
        $domain->delete();

        return response()->noContent();
    }
}

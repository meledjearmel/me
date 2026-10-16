<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DistinctionRequest;
use App\Http\Resources\Api\V1\DistinctionResource;
use App\Models\Distinction;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Distinctions
 */
class DistinctionController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des distinctions
     *
     * Les distinctions publiées alternent dans la carte « Distinction » de la page À propos.
     */
    #[QueryParameter('search', description: 'Recherche dans l’intitulé et l’organisme.', type: 'string')]
    #[QueryParameter('status', description: 'Statut : `published` ou `draft`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return DistinctionResource::collection(
            $this->paginateList(
                Distinction::query()->with('distinguishable')->orderBy('sort_order')->orderByDesc('awarded_on'),
                $request,
                ['title->fr', 'title->en', 'issuer'],
                ['status'],
            )
        );
    }

    /**
     * Ajouter une distinction
     *
     * Pour la rattacher à son contexte : `context_type` (`experience`, `education` ou
     * `project`) et `context_id`.
     */
    public function store(DistinctionRequest $request): DistinctionResource
    {
        $distinction = Distinction::query()->create($request->validated());

        return new DistinctionResource($distinction->refresh()->load('distinguishable'));
    }

    /**
     * Détail d'une distinction
     */
    public function show(Distinction $distinction): DistinctionResource
    {
        return new DistinctionResource($distinction->load('distinguishable'));
    }

    /**
     * Modifier une distinction
     *
     * `context_type` vide détache la distinction de son contexte.
     */
    public function update(DistinctionRequest $request, Distinction $distinction): DistinctionResource
    {
        $distinction->update($request->validated());

        return new DistinctionResource($distinction->refresh()->load('distinguishable'));
    }

    /**
     * Supprimer une distinction
     *
     * La distinction part dans la corbeille (type `distinctions`).
     */
    public function destroy(Distinction $distinction): Response
    {
        $distinction->delete();

        return response()->noContent();
    }
}

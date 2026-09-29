<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CelebrationRequest;
use App\Http\Resources\Api\V1\CelebrationResource;
use App\Models\Celebration;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Surprises
 */
class CelebrationController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des surprises
     *
     * Bonnes nouvelles qu'Armi, l'avatar du site, annonce au hasard aux visiteurs.
     */
    #[QueryParameter('search', description: 'Recherche dans le message.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return CelebrationResource::collection(
            $this->paginateList(Celebration::query()->latest(), $request, ['message->fr', 'message->en'])
        );
    }

    /**
     * Créer une surprise
     */
    public function store(CelebrationRequest $request): CelebrationResource
    {
        return new CelebrationResource(Celebration::query()->create($request->validated())->refresh());
    }

    /**
     * Détail d'une surprise
     */
    public function show(Celebration $celebration): CelebrationResource
    {
        return new CelebrationResource($celebration);
    }

    /**
     * Modifier une surprise
     */
    public function update(CelebrationRequest $request, Celebration $celebration): CelebrationResource
    {
        $celebration->update($request->validated());

        return new CelebrationResource($celebration);
    }

    /**
     * Supprimer une surprise
     *
     * Suppression définitive, compteur de félicitations compris (pas de corbeille).
     */
    public function destroy(Celebration $celebration): Response
    {
        $celebration->delete();

        return response()->noContent();
    }
}

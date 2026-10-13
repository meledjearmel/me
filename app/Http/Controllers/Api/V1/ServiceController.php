<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Models\Service;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Services
 */
class ServiceController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des offres
     *
     * Dans l'ordre de la page « Services ». Seules les offres publiées s'affichent ; sans
     * aucune, la page renvoie une 404.
     */
    #[QueryParameter('search', description: 'Recherche dans le titre et l\'ancre.', type: 'string')]
    #[QueryParameter('status', description: 'Statut : `published` ou `draft`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return ServiceResource::collection(
            $this->paginateList(
                Service::query()->with('projects:id')->orderBy('sort_order'),
                $request,
                ['title->fr', 'title->en', 'slug'],
                ['status'],
            )
        );
    }

    /**
     * Créer une offre
     *
     * L'anglais du titre est obligatoire pour publier. Le prix vient de l'estimateur
     * (`price_mode` = `estimator`, avec `estimator_item_id`), de montants saisis
     * (`manual`, avec `manual_prices`) ou n'est pas affiché (`quote`).
     */
    public function store(ServiceRequest $request): ServiceResource
    {
        $service = Service::query()->create($request->safe()->except('project_ids'));
        $service->projects()->sync($request->validated('project_ids') ?? []);

        return new ServiceResource($service->load('projects:id'));
    }

    /**
     * Détail d'une offre
     */
    public function show(Service $service): ServiceResource
    {
        return new ServiceResource($service->load('projects:id'));
    }

    /**
     * Modifier une offre
     */
    public function update(ServiceRequest $request, Service $service): ServiceResource
    {
        $service->update($request->safe()->except('project_ids'));
        $service->projects()->sync($request->validated('project_ids') ?? []);

        return new ServiceResource($service->load('projects:id'));
    }

    /**
     * Supprimer une offre
     */
    public function destroy(Service $service): Response
    {
        $service->delete();

        return response()->noContent();
    }
}

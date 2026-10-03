<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AppointmentTypeRequest;
use App\Http\Resources\Api\V1\AppointmentTypeResource;
use App\Models\AppointmentType;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Types de rendez-vous
 */
class AppointmentTypeController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des types de rendez-vous
     */
    #[QueryParameter('search', description: 'Recherche dans le nom.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return AppointmentTypeResource::collection(
            $this->paginateList(AppointmentType::query()->orderBy('sort_order'), $request, ['name->fr', 'name->en'])
        );
    }

    /**
     * Créer un type de rendez-vous
     */
    public function store(AppointmentTypeRequest $request): AppointmentTypeResource
    {
        return new AppointmentTypeResource(AppointmentType::query()->create($request->validated()));
    }

    /**
     * Détail d'un type de rendez-vous
     */
    public function show(AppointmentType $appointmentType): AppointmentTypeResource
    {
        return new AppointmentTypeResource($appointmentType);
    }

    /**
     * Modifier un type de rendez-vous
     */
    public function update(AppointmentTypeRequest $request, AppointmentType $appointmentType): AppointmentTypeResource
    {
        $appointmentType->update($request->validated());

        return new AppointmentTypeResource($appointmentType);
    }

    /**
     * Supprimer un type de rendez-vous
     */
    public function destroy(AppointmentType $appointmentType): Response
    {
        $appointmentType->delete();

        return response()->noContent();
    }
}

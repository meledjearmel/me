<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CertificationRequest;
use App\Http\Resources\Api\V1\CertificationResource;
use App\Models\Certification;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Certifications
 */
class CertificationController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des certifications et formations courtes
     *
     * Seules les entrées publiées s'affichent sur la page publique ; sans aucune, la page
     * renvoie une 404.
     */
    #[QueryParameter('search', description: 'Recherche dans le nom et l’organisme.', type: 'string')]
    #[QueryParameter('kind', description: 'Type : `certification` ou `course`.', type: 'string')]
    #[QueryParameter('status', description: 'Statut : `published` ou `draft`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return CertificationResource::collection(
            $this->paginateList(
                Certification::query()->with('media')->orderBy('sort_order')->orderByDesc('issued_on'),
                $request,
                ['name->fr', 'name->en', 'issuer'],
                ['kind', 'status'],
            )
        );
    }

    /**
     * Ajouter une certification ou une formation
     *
     * En `multipart/form-data` pour joindre un badge (`badge`, image de 2 Mo au plus).
     */
    public function store(CertificationRequest $request): CertificationResource
    {
        $certification = Certification::query()->create($request->safe()->except('badge'));
        $this->syncBadge($certification, $request);

        return new CertificationResource($certification->refresh());
    }

    /**
     * Détail d'une certification ou d'une formation
     */
    public function show(Certification $certification): CertificationResource
    {
        return new CertificationResource($certification);
    }

    /**
     * Modifier une certification ou une formation
     *
     * Avec un badge : `POST` en `multipart/form-data` et `_method=PUT`.
     */
    public function update(CertificationRequest $request, Certification $certification): CertificationResource
    {
        $certification->update($request->safe()->except('badge'));
        $this->syncBadge($certification, $request);

        return new CertificationResource($certification->refresh());
    }

    /**
     * Supprimer une certification ou une formation
     *
     * L'entrée part dans la corbeille (type `certifications`).
     */
    public function destroy(Certification $certification): Response
    {
        $certification->delete();

        return response()->noContent();
    }

    /**
     * Retirer le badge d'une certification
     */
    public function destroyBadge(Certification $certification): CertificationResource
    {
        $certification->clearMediaCollection('badge');

        return new CertificationResource($certification->refresh());
    }

    private function syncBadge(Certification $certification, CertificationRequest $request): void
    {
        if ($request->hasFile('badge')) {
            $certification->addMediaFromRequest('badge')->toMediaCollection('badge');
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\JobProfileRequest;
use App\Http\Resources\Api\V1\JobProfileResource;
use App\Models\JobProfile;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Profils métier
 */
class JobProfileController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des profils métier
     */
    #[QueryParameter('search', description: 'Recherche dans la clé et le libellé.', type: 'string')]
    #[QueryParameter('status', description: 'Statut de publication : `draft` ou `published`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return JobProfileResource::collection(
            $this->paginateList(JobProfile::query()->orderBy('sort_order'), $request, ['key', 'label->fr', 'label->en'], ['status'])
        );
    }

    /**
     * Créer un profil métier
     */
    public function store(JobProfileRequest $request): JobProfileResource
    {
        return new JobProfileResource(JobProfile::query()->create($request->validated()));
    }

    /**
     * Détail d'un profil métier
     */
    public function show(JobProfile $jobProfile): JobProfileResource
    {
        return new JobProfileResource($jobProfile);
    }

    /**
     * Modifier un profil métier
     *
     * Avec `cv_file_fr` / `cv_file_en` (CV en PDF, 10 Mo max), envoyer un `POST` en
     * `multipart/form-data` avec le champ `_method=PATCH` : PHP ne lit pas les fichiers d'une
     * requête `PATCH` directe. Un nouveau fichier remplace l'ancien.
     */
    public function update(JobProfileRequest $request, JobProfile $jobProfile): JobProfileResource
    {
        $jobProfile->update($request->safe()->except(['cv_file_fr', 'cv_file_en']));

        foreach (JobProfile::CV_LOCALES as $locale) {
            if ($request->hasFile('cv_file_'.$locale)) {
                $jobProfile->addMediaFromRequest('cv_file_'.$locale)->toMediaCollection(JobProfile::cvFileCollection($locale));
            }
        }

        return new JobProfileResource($jobProfile->refresh());
    }

    /**
     * Supprimer un profil métier
     */
    public function destroy(JobProfile $jobProfile): Response
    {
        $jobProfile->delete();

        return response()->noContent();
    }

    /**
     * Retirer le CV uploadé d'une langue pour ce profil métier
     *
     * Sans fichier pour une langue, le CV de l'autre langue sert de secours, sinon le CV est généré.
     */
    public function destroyCv(JobProfile $jobProfile, string $locale): JobProfileResource
    {
        $jobProfile->clearMediaCollection(JobProfile::cvFileCollection($locale));

        return new JobProfileResource($jobProfile->refresh());
    }
}

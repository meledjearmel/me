<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\JobApplicationRequest;
use App\Http\Resources\Api\V1\JobApplicationResource;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Candidatures
 */
class JobApplicationController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des pages de candidature
     *
     * Les plus récentes d'abord, avec leurs compteurs de consultations et de CV téléchargés.
     */
    #[QueryParameter('search', description: 'Recherche dans l\'entreprise, le poste et le contact.', type: 'string')]
    #[QueryParameter('status', description: 'Statut : `draft`, `active` ou `archived`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return JobApplicationResource::collection(
            $this->paginateList(
                JobApplication::query()->with('projects:id')->latest(),
                $request,
                ['company', 'position', 'contact_name'],
                ['status'],
            )
        );
    }

    /**
     * Créer une page de candidature
     *
     * Son adresse privée (`token`, `url`) est créée d'après le nom de l'entreprise et ne
     * change plus. Le lien ne répond qu'au statut `active`.
     */
    public function store(JobApplicationRequest $request): JobApplicationResource
    {
        $application = JobApplication::query()->create($request->safe()->except('project_ids'));
        $application->syncProjects($request->validated('project_ids') ?? []);

        return new JobApplicationResource($application->load('projects:id'));
    }

    /**
     * Détail d'une page de candidature
     */
    public function show(JobApplication $jobApplication): JobApplicationResource
    {
        return new JobApplicationResource($jobApplication->load('projects:id'));
    }

    /**
     * Ce qui s'est passé sur une page de candidature
     *
     * Consultations (`view`, une par visiteur et par jour) et téléchargements du CV
     * (`cv_download`), les plus récents d'abord (100 au plus), avec le pays, la ville et
     * l'appareil.
     *
     * @response array{data: list<array{id: int, type: string, country_code: string|null, country: string|null, city: string|null, device: string|null, referrer_host: string|null, created_at: string|null}>}
     */
    public function events(JobApplication $jobApplication): JsonResponse
    {
        return response()->json([
            'data' => $jobApplication->events()->latest('created_at')->limit(100)->get()
                ->map(fn (JobApplicationEvent $event): array => $event->only(['id', 'type', 'country_code', 'country', 'city', 'device', 'referrer_host', 'created_at']))
                ->all(),
        ]);
    }

    /**
     * Modifier une page de candidature
     */
    public function update(JobApplicationRequest $request, JobApplication $jobApplication): JobApplicationResource
    {
        $jobApplication->update($request->safe()->except('project_ids'));
        $jobApplication->syncProjects($request->validated('project_ids') ?? []);

        return new JobApplicationResource($jobApplication->load('projects:id'));
    }

    /**
     * Supprimer une page de candidature
     */
    public function destroy(JobApplication $jobApplication): Response
    {
        $jobApplication->delete();

        return response()->noContent();
    }
}

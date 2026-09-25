<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Concerns\SavesProjects;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectRequest;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Models\Project;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * @tags Projets
 */
class ProjectController extends Controller
{
    use PaginatesAdminLists, SavesProjects;

    /**
     * Liste des projets
     */
    #[QueryParameter('search', description: 'Recherche dans le titre et le slug.', type: 'string')]
    #[QueryParameter('status', description: 'Statut : `published` ou `archived`.', type: 'string')]
    #[QueryParameter('is_featured', description: 'Projets à la une : `1` ou `0`.', type: 'string')]
    #[QueryParameter('is_open_source', description: 'Projets open source : `1` ou `0`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return ProjectResource::collection(
            $this->paginateList(Project::query()->with('media')->orderBy('sort_order'), $request, ['title->fr', 'title->en', 'slug'], ['status', 'is_featured', 'is_open_source'])
        );
    }

    /**
     * Créer un projet
     *
     * Requête `multipart/form-data` si `cover` (image) ou `gallery[]` (images) sont envoyés.
     * `domains`, `job_profiles`, `technologies` et `related_projects` sont des listes d'identifiants.
     */
    public function store(ProjectRequest $request): ProjectResource
    {
        $project = DB::transaction(function () use ($request): Project {
            $project = Project::query()->create($this->projectAttributes($request));

            $this->syncRelations($project, $request);

            return $project;
        });

        $this->syncMedia($project, $request);

        return $this->respond($project);
    }

    /**
     * Détail d'un projet
     */
    public function show(Project $project): ProjectResource
    {
        return $this->respond($project);
    }

    /**
     * Modifier un projet
     *
     * Avec des fichiers, envoyer un `POST` en `multipart/form-data` avec le champ `_method=PUT` :
     * PHP ne lit pas les fichiers d'une requête `PUT` directe. La galerie s'enrichit ; la couverture est remplacée.
     */
    public function update(ProjectRequest $request, Project $project): ProjectResource
    {
        DB::transaction(function () use ($request, $project): void {
            $project->update($this->projectAttributes($request));

            $this->syncRelations($project, $request);
        });

        $this->syncMedia($project, $request);

        return $this->respond($project);
    }

    /**
     * Supprimer un projet
     */
    public function destroy(Project $project): Response
    {
        $project->delete();

        return response()->noContent();
    }

    private function respond(Project $project): ProjectResource
    {
        return (new ProjectResource(
            $project->refresh()->load(['domains', 'jobProfiles', 'technologies', 'media']),
        ))->withRelatedProjectIds($this->relatedProjectIds($project));
    }
}

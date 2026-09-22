<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectRequest;
use App\Models\Domain;
use App\Models\JobProfile;
use App\Models\Project;
use App\Models\Technology;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/projects/index', [
            'projects' => Project::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/projects/create', $this->formOptions());
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $project = DB::transaction(function () use ($request): Project {
            $project = Project::query()->create($request->safe()->except([
                'cover', 'gallery', 'domains', 'job_profiles', 'technologies', 'related_projects',
            ]));

            $this->syncRelations($project, $request);

            return $project;
        });

        $this->syncMedia($project, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Projet créé.')]);

        return to_route('admin.projects.index');
    }

    public function edit(Project $project): Response
    {
        $project->load(['domains', 'jobProfiles', 'technologies']);

        return Inertia::render('admin/projects/edit', [
            'project' => [
                ...$project->toArray(),
                'job_profiles' => $project->jobProfiles,
                'cover_url' => $project->getFirstMediaUrl('cover') ?: null,
                'gallery_urls' => $project->getMedia('gallery')->map->getUrl()->values(),
            ],
            'relatedProjectIds' => $this->relatedProjectIds($project),
            ...$this->formOptions(),
        ]);
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        DB::transaction(function () use ($request, $project): void {
            $project->update($request->safe()->except([
                'cover', 'gallery', 'domains', 'job_profiles', 'technologies', 'related_projects',
            ]));

            $this->syncRelations($project, $request);
        });

        $this->syncMedia($project, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Projet mis à jour.')]);

        return to_route('admin.projects.index');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Projet supprimé.')]);

        return to_route('admin.projects.index');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'domains' => Domain::query()->orderBy('sort_order')->get(),
            'jobProfiles' => JobProfile::query()->orderBy('sort_order')->get(),
            'technologies' => Technology::query()->orderBy('name')->get(),
            'projects' => Project::query()->orderBy('sort_order')->get(['id', 'title', 'slug']),
        ];
    }

    private function syncRelations(Project $project, ProjectRequest $request): void
    {
        $project->domains()->sync($request->validated('domains', []));
        $project->jobProfiles()->sync($request->validated('job_profiles', []));
        $project->technologies()->sync($request->validated('technologies', []));
        $this->syncRelatedProjects($project, $request->validated('related_projects', []));
    }

    /**
     * Maintient la relation "projets liés" symétrique : chaque paire est stockée
     * dans les deux sens pour que le groupement apparaisse depuis chaque projet.
     *
     * @param  array<int, int>  $relatedIds
     */
    private function syncRelatedProjects(Project $project, array $relatedIds): void
    {
        DB::table('project_related')
            ->where('project_id', $project->id)
            ->orWhere('related_project_id', $project->id)
            ->delete();

        foreach ($relatedIds as $relatedId) {
            DB::table('project_related')->insert(['project_id' => $project->id, 'related_project_id' => $relatedId]);
            DB::table('project_related')->insert(['project_id' => $relatedId, 'related_project_id' => $project->id]);
        }
    }

    /** @return array<int, int> */
    private function relatedProjectIds(Project $project): array
    {
        return DB::table('project_related')
            ->where('project_id', $project->id)
            ->orWhere('related_project_id', $project->id)
            ->get()
            ->flatMap(fn ($row) => [$row->project_id, $row->related_project_id])
            ->reject(fn (int $id): bool => $id === $project->id)
            ->unique()
            ->values()
            ->all();
    }

    private function syncMedia(Project $project, ProjectRequest $request): void
    {
        if ($request->hasFile('cover')) {
            $project->addMediaFromRequest('cover')->toMediaCollection('cover');
        }

        foreach ($request->file('gallery', []) as $file) {
            $project->addMedia($file)->toMediaCollection('gallery');
        }
    }
}

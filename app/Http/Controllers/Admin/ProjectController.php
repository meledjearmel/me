<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Concerns\SavesProjects;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectRequest;
use App\Models\Domain;
use App\Models\JobProfile;
use App\Models\Project;
use App\Models\Technology;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    use PaginatesAdminLists, SavesProjects;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/projects/index', [
            'projects' => $this->paginateList(Project::query()->orderBy('sort_order'), $request, ['title->fr', 'title->en', 'slug'], ['status', 'is_featured', 'is_open_source']),
            'filters' => $this->listFilters($request, ['status', 'is_featured', 'is_open_source']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/projects/create', $this->formOptions());
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $project = DB::transaction(function () use ($request): Project {
            $project = Project::query()->create($this->projectAttributes($request));

            $this->syncRelations($project, $request);

            return $project;
        });

        $this->syncMedia($project, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Projet créé.')]);

        return to_route('admin.projects.index');
    }

    public function show(Project $project): Response
    {
        return Inertia::render('admin/projects/show', [
            'project' => [
                ...$project->load(['domains', 'jobProfiles', 'technologies', 'relatedProjects'])->toArray(),
                'job_profiles' => $project->jobProfiles,
                'related_projects' => $project->relatedProjects,
                'cover_url' => $project->getFirstMediaUrl('cover') ?: null,
                'gallery_urls' => $project->getMedia('gallery')->map->getUrl()->values(),
            ],
        ]);
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
            $project->update($this->projectAttributes($request));

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
}

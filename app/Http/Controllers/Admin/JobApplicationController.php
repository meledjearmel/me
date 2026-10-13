<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\JobApplicationRequest;
use App\Models\JobApplication;
use App\Models\JobProfile;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Les pages de candidature : une par entreprise, avec son lien privé, ses projets, le CV du
 * profil visé, et le suivi de ce qui s'y passe (consultations, téléchargements du CV).
 */
class JobApplicationController extends Controller
{
    use PaginatesAdminLists;

    /** @var list<string> */
    private const array FILTERABLE = ['status'];

    public function index(Request $request): Response
    {
        return Inertia::render('admin/job-applications/index', [
            'applications' => $this->paginateList(
                JobApplication::query()->latest(),
                $request,
                ['company', 'position', 'contact_name'],
                self::FILTERABLE,
            ),
            'filters' => $this->listFilters($request, self::FILTERABLE),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/job-applications/create', $this->formOptions());
    }

    public function store(JobApplicationRequest $request): RedirectResponse
    {
        $application = JobApplication::query()->create($request->safe()->except('project_ids'));
        $application->syncProjects($request->validated('project_ids') ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Page de candidature créée.')]);

        return to_route('admin.job-applications.show', $application);
    }

    public function show(JobApplication $jobApplication): Response
    {
        $jobApplication->load(['jobProfile', 'projects']);

        return Inertia::render('admin/job-applications/show', [
            'application' => [
                ...$jobApplication->toArray(),
                'url' => route('job-applications.show', ['locale' => $jobApplication->locale, 'token' => $jobApplication->token]),
                'is_open' => $jobApplication->isOpen(),
                'job_profile' => $jobApplication->jobProfile?->getTranslation('label', 'fr'),
                'projects' => $jobApplication->projects->map(fn (Project $project): array => [
                    'id' => $project->id,
                    'title' => $project->getTranslation('title', 'fr'),
                    'published' => $project->status === ProjectStatus::Published,
                ])->all(),
            ],
            'events' => $jobApplication->events()->latest('created_at')->limit(100)->get(),
        ]);
    }

    public function edit(JobApplication $jobApplication): Response
    {
        return Inertia::render('admin/job-applications/edit', [
            'application' => [
                ...$jobApplication->toArray(),
                'project_ids' => $jobApplication->projects()->pluck('projects.id')->all(),
            ],
            ...$this->formOptions(),
        ]);
    }

    public function update(JobApplicationRequest $request, JobApplication $jobApplication): RedirectResponse
    {
        $jobApplication->update($request->safe()->except('project_ids'));
        $jobApplication->syncProjects($request->validated('project_ids') ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Page de candidature mise à jour.')]);

        return to_route('admin.job-applications.show', $jobApplication);
    }

    public function destroy(JobApplication $jobApplication): RedirectResponse
    {
        $jobApplication->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Page de candidature supprimée.')]);

        return to_route('admin.job-applications.index');
    }

    /**
     * Les profils métier (pour le CV) et les projets publiés qu'une page peut montrer.
     *
     * @return array{jobProfiles: list<array{id: int, label: string}>, projects: list<array{id: int, label: string}>}
     */
    private function formOptions(): array
    {
        return [
            'jobProfiles' => JobProfile::query()->published()->orderBy('sort_order')->get()
                ->map(fn (JobProfile $profile): array => ['id' => $profile->id, 'label' => $profile->getTranslation('label', 'fr')])
                ->all(),
            'projects' => Project::query()->where('status', ProjectStatus::Published)->orderBy('sort_order')->get()
                ->map(fn (Project $project): array => ['id' => $project->id, 'label' => $project->getTranslation('title', 'fr')])
                ->all(),
        ];
    }
}

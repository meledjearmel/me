<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\JobProfileRequest;
use App\Models\JobProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobProfileController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/job-profiles/index', [
            'jobProfiles' => $this->paginateList(JobProfile::query()->orderBy('sort_order'), $request, ['key', 'label->fr', 'label->en'], ['status']),
            'filters' => $this->listFilters($request, ['status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/job-profiles/create');
    }

    public function store(JobProfileRequest $request): RedirectResponse
    {
        JobProfile::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profil métier créé.')]);

        return to_route('admin.job-profiles.index');
    }

    public function show(JobProfile $jobProfile): Response
    {
        return Inertia::render('admin/job-profiles/show', [
            'jobProfile' => $jobProfile->load(['projects' => fn ($query) => $query->orderBy('sort_order')]),
        ]);
    }

    public function edit(JobProfile $jobProfile): Response
    {
        return Inertia::render('admin/job-profiles/edit', [
            'jobProfile' => [
                ...$jobProfile->toArray(),
                'cv_files' => collect(JobProfile::CV_LOCALES)
                    ->mapWithKeys(function (string $locale) use ($jobProfile): array {
                        $media = $jobProfile->getFirstMedia(JobProfile::cvFileCollection($locale));

                        return [$locale => $media === null ? null : [
                            'file_name' => $media->file_name,
                            'url' => $media->getUrl(),
                        ]];
                    }),
            ],
        ]);
    }

    public function update(JobProfileRequest $request, JobProfile $jobProfile): RedirectResponse
    {
        $jobProfile->update($request->safe()->except(['cv_file_fr', 'cv_file_en']));

        foreach (JobProfile::CV_LOCALES as $locale) {
            if ($request->hasFile('cv_file_'.$locale)) {
                $jobProfile->addMediaFromRequest('cv_file_'.$locale)->toMediaCollection(JobProfile::cvFileCollection($locale));
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profil métier mis à jour.')]);

        return to_route('admin.job-profiles.index');
    }

    public function destroy(JobProfile $jobProfile): RedirectResponse
    {
        $jobProfile->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profil métier supprimé.')]);

        return to_route('admin.job-profiles.index');
    }

    /** Retire le CV uploadé d'une langue pour ce profil métier : le CV redevient généré. */
    public function destroyCv(JobProfile $jobProfile, string $locale): RedirectResponse
    {
        $jobProfile->clearMediaCollection(JobProfile::cvFileCollection($locale));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('CV retiré.')]);

        return to_route('admin.job-profiles.edit', $jobProfile);
    }
}

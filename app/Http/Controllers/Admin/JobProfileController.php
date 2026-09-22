<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\JobProfileRequest;
use App\Models\JobProfile;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class JobProfileController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/job-profiles/index', [
            'jobProfiles' => JobProfile::query()->orderBy('sort_order')->get(),
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

    public function edit(JobProfile $jobProfile): Response
    {
        return Inertia::render('admin/job-profiles/edit', [
            'jobProfile' => $jobProfile,
        ]);
    }

    public function update(JobProfileRequest $request, JobProfile $jobProfile): RedirectResponse
    {
        $jobProfile->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profil métier mis à jour.')]);

        return to_route('admin.job-profiles.index');
    }

    public function destroy(JobProfile $jobProfile): RedirectResponse
    {
        $jobProfile->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profil métier supprimé.')]);

        return to_route('admin.job-profiles.index');
    }
}

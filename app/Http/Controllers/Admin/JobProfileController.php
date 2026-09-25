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

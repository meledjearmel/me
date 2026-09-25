<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EducationRequest;
use App\Models\Education;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EducationController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/educations/index', [
            'educations' => $this->paginateList(Education::query()->orderBy('sort_order'), $request, ['institution', 'degree->fr', 'degree->en', 'field->fr', 'field->en'], ['status']),
            'filters' => $this->listFilters($request, ['status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/educations/create');
    }

    public function store(EducationRequest $request): RedirectResponse
    {
        Education::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Formation créée.')]);

        return to_route('admin.educations.index');
    }

    public function show(Education $education): Response
    {
        return Inertia::render('admin/educations/show', [
            'education' => $education,
        ]);
    }

    public function edit(Education $education): Response
    {
        return Inertia::render('admin/educations/edit', [
            'education' => $education,
        ]);
    }

    public function update(EducationRequest $request, Education $education): RedirectResponse
    {
        $education->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Formation mise à jour.')]);

        return to_route('admin.educations.index');
    }

    public function destroy(Education $education): RedirectResponse
    {
        $education->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Formation supprimée.')]);

        return to_route('admin.educations.index');
    }
}

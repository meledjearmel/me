<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProfessionalReferenceRequest;
use App\Models\ProfessionalReference;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfessionalReferenceController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/professional-references/index', [
            'professionalReferences' => $this->paginateList(ProfessionalReference::query()->with('project')->latest(), $request, ['name', 'role', 'company', 'email'], ['is_public']),
            'filters' => $this->listFilters($request, ['is_public']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/professional-references/create', [
            'projects' => Project::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(ProfessionalReferenceRequest $request): RedirectResponse
    {
        ProfessionalReference::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Référence créée.')]);

        return to_route('admin.professional-references.index');
    }

    public function show(ProfessionalReference $professionalReference): Response
    {
        return Inertia::render('admin/professional-references/show', [
            'professionalReference' => $professionalReference->load('project'),
        ]);
    }

    public function edit(ProfessionalReference $professionalReference): Response
    {
        return Inertia::render('admin/professional-references/edit', [
            'professionalReference' => $professionalReference,
            'projects' => Project::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(ProfessionalReferenceRequest $request, ProfessionalReference $professionalReference): RedirectResponse
    {
        $professionalReference->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Référence mise à jour.')]);

        return to_route('admin.professional-references.index');
    }

    public function destroy(ProfessionalReference $professionalReference): RedirectResponse
    {
        $professionalReference->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Référence supprimée.')]);

        return to_route('admin.professional-references.index');
    }
}

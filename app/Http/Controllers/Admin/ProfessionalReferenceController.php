<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProfessionalReferenceRequest;
use App\Models\ProfessionalReference;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProfessionalReferenceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/professional-references/index', [
            'professionalReferences' => ProfessionalReference::query()->with('project')->latest()->get(),
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

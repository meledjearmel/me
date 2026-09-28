<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TechnologyRequest;
use App\Models\Technology;
use App\Models\TechnologyCategory;
use App\Services\TechnologyIconLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TechnologyController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/technologies/index', [
            'technologies' => $this->paginateList(Technology::query()->with('category')->orderBy('name'), $request, ['name'], ['category_id']),
            'filters' => $this->listFilters($request, ['category_id']),
            'categories' => TechnologyCategory::query()->orderBy('sort_order')->get(['id', 'label']),
        ]);
    }

    public function create(TechnologyIconLibrary $library): Response
    {
        return Inertia::render('admin/technologies/create', [
            'icons' => $library->all(),
            'categories' => TechnologyCategory::query()->orderBy('sort_order')->get(['id', 'label']),
        ]);
    }

    public function store(TechnologyRequest $request): RedirectResponse
    {
        Technology::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Technologie créée.')]);

        return to_route('admin.technologies.index');
    }

    public function show(Technology $technology): Response
    {
        return Inertia::render('admin/technologies/show', [
            'technology' => $technology->load(['category', 'projects' => fn ($query) => $query->orderBy('sort_order')]),
        ]);
    }

    public function edit(Technology $technology, TechnologyIconLibrary $library): Response
    {
        return Inertia::render('admin/technologies/edit', [
            'technology' => $technology,
            'icons' => $library->all(),
            'categories' => TechnologyCategory::query()->orderBy('sort_order')->get(['id', 'label']),
        ]);
    }

    public function update(TechnologyRequest $request, Technology $technology): RedirectResponse
    {
        $technology->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Technologie mise à jour.')]);

        return to_route('admin.technologies.index');
    }

    public function destroy(Technology $technology): RedirectResponse
    {
        $technology->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Technologie supprimée.')]);

        return to_route('admin.technologies.index');
    }
}

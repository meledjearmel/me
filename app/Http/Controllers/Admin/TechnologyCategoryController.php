<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TechnologyCategoryRequest;
use App\Models\TechnologyCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TechnologyCategoryController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/technology-categories/index', [
            'categories' => $this->paginateList(TechnologyCategory::query()->withCount('technologies')->orderBy('sort_order'), $request, ['key', 'label->fr', 'label->en']),
            'filters' => $this->listFilters($request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/technology-categories/create');
    }

    public function store(TechnologyCategoryRequest $request): RedirectResponse
    {
        TechnologyCategory::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Catégorie créée.')]);

        return to_route('admin.technology-categories.index');
    }

    public function show(TechnologyCategory $technologyCategory): Response
    {
        return Inertia::render('admin/technology-categories/show', [
            'category' => $technologyCategory->load(['technologies' => fn ($query) => $query->orderBy('name')]),
        ]);
    }

    public function edit(TechnologyCategory $technologyCategory): Response
    {
        return Inertia::render('admin/technology-categories/edit', [
            'category' => $technologyCategory,
        ]);
    }

    public function update(TechnologyCategoryRequest $request, TechnologyCategory $technologyCategory): RedirectResponse
    {
        $technologyCategory->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Catégorie mise à jour.')]);

        return to_route('admin.technology-categories.index');
    }

    public function destroy(TechnologyCategory $technologyCategory): RedirectResponse
    {
        $technologyCategory->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Catégorie supprimée.')]);

        return to_route('admin.technology-categories.index');
    }
}

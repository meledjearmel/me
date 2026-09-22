<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TechnologyRequest;
use App\Models\Technology;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TechnologyController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/technologies/index', [
            'technologies' => Technology::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/technologies/create');
    }

    public function store(TechnologyRequest $request): RedirectResponse
    {
        Technology::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Technologie créée.')]);

        return to_route('admin.technologies.index');
    }

    public function edit(Technology $technology): Response
    {
        return Inertia::render('admin/technologies/edit', [
            'technology' => $technology,
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

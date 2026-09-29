<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CelebrationRequest;
use App\Models\Celebration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CelebrationController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/celebrations/index', [
            'celebrations' => $this->paginateList(Celebration::query()->latest(), $request, ['message->fr', 'message->en']),
            'filters' => $this->listFilters($request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/celebrations/create');
    }

    public function store(CelebrationRequest $request): RedirectResponse
    {
        Celebration::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Surprise créée.')]);

        return to_route('admin.celebrations.index');
    }

    public function show(Celebration $celebration): Response
    {
        return Inertia::render('admin/celebrations/show', [
            'celebration' => $celebration,
        ]);
    }

    public function edit(Celebration $celebration): Response
    {
        return Inertia::render('admin/celebrations/edit', [
            'celebration' => $celebration,
        ]);
    }

    public function update(CelebrationRequest $request, Celebration $celebration): RedirectResponse
    {
        $celebration->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Surprise mise à jour.')]);

        return to_route('admin.celebrations.index');
    }

    public function destroy(Celebration $celebration): RedirectResponse
    {
        $celebration->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Surprise supprimée.')]);

        return to_route('admin.celebrations.index');
    }
}

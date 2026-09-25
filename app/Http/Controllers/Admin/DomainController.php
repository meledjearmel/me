<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DomainRequest;
use App\Models\Domain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DomainController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/domains/index', [
            'domains' => $this->paginateList(Domain::query()->orderBy('sort_order'), $request, ['key', 'label->fr', 'label->en'], ['status']),
            'filters' => $this->listFilters($request, ['status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/domains/create');
    }

    public function store(DomainRequest $request): RedirectResponse
    {
        Domain::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Domaine créé.')]);

        return to_route('admin.domains.index');
    }

    public function show(Domain $domain): Response
    {
        return Inertia::render('admin/domains/show', [
            'domain' => $domain->load(['skills' => fn ($query) => $query->orderBy('sort_order')])->loadCount('projects'),
        ]);
    }

    public function edit(Domain $domain): Response
    {
        return Inertia::render('admin/domains/edit', [
            'domain' => $domain,
        ]);
    }

    public function update(DomainRequest $request, Domain $domain): RedirectResponse
    {
        $domain->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Domaine mis à jour.')]);

        return to_route('admin.domains.index');
    }

    public function destroy(Domain $domain): RedirectResponse
    {
        $domain->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Domaine supprimé.')]);

        return to_route('admin.domains.index');
    }
}

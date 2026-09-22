<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DomainRequest;
use App\Models\Domain;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DomainController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/domains/index', [
            'domains' => Domain::query()->orderBy('sort_order')->get(),
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

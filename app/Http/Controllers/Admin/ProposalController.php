<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProposalRequest;
use App\Models\Proposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProposalController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/proposals/index', [
            'proposals' => $this->paginateList(Proposal::query()->latest(), $request, ['job_title', 'client_name', 'body'], ['platform', 'status']),
            'filters' => $this->listFilters($request, ['platform', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/proposals/create');
    }

    public function store(ProposalRequest $request): RedirectResponse
    {
        Proposal::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Proposition enregistrée.')]);

        return to_route('admin.proposals.index');
    }

    public function edit(Proposal $proposal): Response
    {
        return Inertia::render('admin/proposals/edit', [
            'proposal' => $proposal,
        ]);
    }

    public function update(ProposalRequest $request, Proposal $proposal): RedirectResponse
    {
        $proposal->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Proposition mise à jour.')]);

        return to_route('admin.proposals.index');
    }

    public function destroy(Proposal $proposal): RedirectResponse
    {
        $proposal->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Proposition supprimée.')]);

        return to_route('admin.proposals.index');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Enums\EngagementStatus;
use App\Http\Controllers\Controller;
use App\Models\Engagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EngagementController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/engagements/index', [
            'engagements' => $this->paginateList(Engagement::query()->with('jobProfile')->latest(), $request, ['name', 'email', 'company', 'subject'], ['type', 'status']),
            'filters' => $this->listFilters($request, ['type', 'status']),
        ]);
    }

    public function show(Engagement $engagement): Response
    {
        return Inertia::render('admin/engagements/show', [
            'engagement' => $engagement->load('jobProfile'),
        ]);
    }

    /** Marque une demande comme traitée (ou la remet « nouvelle »). */
    public function update(Engagement $engagement): RedirectResponse
    {
        $engagement->update([
            'status' => $engagement->status === EngagementStatus::New
                ? EngagementStatus::Handled
                : EngagementStatus::New,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Demande mise à jour.')]);

        return back();
    }

    public function destroy(Engagement $engagement): RedirectResponse
    {
        $engagement->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Demande supprimée.')]);

        return to_route('admin.engagements.index');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AppointmentTypeRequest;
use App\Models\AppointmentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentTypeController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/appointment-types/index', [
            'appointmentTypes' => $this->paginateList(AppointmentType::query()->withCount('appointments')->orderBy('sort_order'), $request, ['name->fr', 'name->en']),
            'filters' => $this->listFilters($request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/appointment-types/create');
    }

    public function store(AppointmentTypeRequest $request): RedirectResponse
    {
        AppointmentType::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Type de rendez-vous créé.')]);

        return to_route('admin.appointment-types.index');
    }

    public function edit(AppointmentType $appointmentType): Response
    {
        return Inertia::render('admin/appointment-types/edit', [
            'appointmentType' => $appointmentType,
        ]);
    }

    public function update(AppointmentTypeRequest $request, AppointmentType $appointmentType): RedirectResponse
    {
        $appointmentType->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Type de rendez-vous mis à jour.')]);

        return to_route('admin.appointment-types.index');
    }

    public function destroy(AppointmentType $appointmentType): RedirectResponse
    {
        $appointmentType->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Type de rendez-vous supprimé.')]);

        return to_route('admin.appointment-types.index');
    }
}

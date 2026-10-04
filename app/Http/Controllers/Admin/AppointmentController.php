<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AppointmentDecisionRequest;
use App\Models\Appointment;
use App\Services\BookingCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    use PaginatesAdminLists;

    public function __construct(private BookingCalendar $calendar) {}

    public function index(Request $request): Response
    {
        return Inertia::render('admin/appointments/index', [
            'appointments' => $this->paginateList(Appointment::query()->with('appointmentType')->latest('starts_at'), $request, ['name', 'email', 'company', 'phone'], ['status', 'location']),
            'filters' => $this->listFilters($request, ['status', 'location']),
        ]);
    }

    public function show(Appointment $appointment): Response
    {
        return Inertia::render('admin/appointments/show', [
            'appointment' => $appointment->load('appointmentType'),
            // Visio : lien Jitsi créé à la confirmation, ou mon lien fixe pré-rempli.
            'defaultVideoLink' => $this->calendar->settings()->booking_video_provider === 'link'
                ? $this->calendar->settings()->booking_video_link
                : null,
            'videoProvider' => $this->calendar->settings()->booking_video_provider,
        ]);
    }

    public function confirm(AppointmentDecisionRequest $request, Appointment $appointment): RedirectResponse
    {
        $this->calendar->confirm($appointment, $request->validated('meeting_details'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Rendez-vous confirmé.')]);

        return back();
    }

    public function decline(AppointmentDecisionRequest $request, Appointment $appointment): RedirectResponse
    {
        $this->calendar->decline($appointment, $request->validated('decline_reason'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Rendez-vous refusé.')]);

        return back();
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        // Une demande supprimée ne doit pas garder son créneau.
        $this->calendar->release($appointment);
        $appointment->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Rendez-vous supprimé.')]);

        return to_route('admin.appointments.index');
    }
}

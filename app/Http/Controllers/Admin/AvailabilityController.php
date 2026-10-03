<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AvailabilityRuleRequest;
use App\Http\Requests\Admin\BlockedPeriodRequest;
use App\Http\Requests\Admin\BookingSettingsRequest;
use App\Services\BookingCalendar;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Zap\Models\Schedule;

/** Mes disponibilités pour les rendez-vous : réglages, plages hebdomadaires et périodes bloquées. */
class AvailabilityController extends Controller
{
    public function __construct(private BookingCalendar $calendar) {}

    public function index(): Response
    {
        return Inertia::render('admin/availability/index', [
            'settings' => $this->calendar->settings(),
            'rules' => $this->calendar->availabilityRules(),
            'blockedPeriods' => $this->calendar->blockedPeriods(),
        ]);
    }

    public function updateSettings(BookingSettingsRequest $request): RedirectResponse
    {
        $this->calendar->settings()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Réglages enregistrés.')]);

        return back();
    }

    public function storeRule(AvailabilityRuleRequest $request): RedirectResponse
    {
        $this->calendar->addAvailability($request->validated('days'), $request->validated('start'), $request->validated('end'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Disponibilité ajoutée.')]);

        return back();
    }

    public function storeBlockedPeriod(BlockedPeriodRequest $request): RedirectResponse
    {
        $this->calendar->addBlockedPeriod($request->validated('from'), $request->validated('to'), $request->validated('label'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Période bloquée ajoutée.')]);

        return back();
    }

    /** Retire une disponibilité ou une période bloquée. */
    public function destroy(Schedule $schedule): RedirectResponse
    {
        $this->calendar->removeRule($schedule);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supprimé.')]);

        return back();
    }
}

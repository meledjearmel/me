<?php

namespace App\Http\Controllers;

use App\Jobs\SendPushNotification;
use App\Models\Appointment;
use App\Services\BookingCalendar;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Annulation d'un rendez-vous par le visiteur, depuis le lien reçu par email
 * (le jeton d'annulation tient lieu d'identification).
 */
class AppointmentCancellationController extends Controller
{
    public function __construct(private BookingCalendar $calendar) {}

    public function show(string $locale, string $token): Response
    {
        $appointment = $this->find($token);

        return Inertia::render('public/appointment-cancel', [
            'appointment' => [
                'type' => $appointment->appointmentType?->getTranslation('name', app()->getLocale()),
                'starts_at' => $appointment->starts_at->utc()->toIso8601ZuluString(),
                'timezone' => $appointment->timezone,
                'location' => $appointment->location,
                // Déjà refusé, annulé, ou déjà passé : il n'y a plus rien à annuler.
                'cancellable' => $appointment->status->holdsSlot() && $appointment->starts_at->isFuture(),
                'cancelled' => ! $appointment->status->holdsSlot(),
            ],
            'token' => $token,
        ]);
    }

    public function store(string $locale, string $token): RedirectResponse
    {
        $appointment = $this->find($token);

        abort_unless($appointment->starts_at->isFuture(), 409, __('Ce rendez-vous est déjà passé.'));

        $this->calendar->cancel($appointment);

        SendPushNotification::dispatch('Rendez-vous annulé', $appointment->name, ['type' => 'appointment', 'id' => (string) $appointment->id]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Votre rendez-vous est annulé.')]);

        return back();
    }

    private function find(string $token): Appointment
    {
        return Appointment::query()->with('appointmentType')->where('cancel_token', $token)->firstOrFail();
    }
}

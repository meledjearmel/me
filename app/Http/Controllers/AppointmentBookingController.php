<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\AppointmentBookingRequest;
use App\Http\Resources\Public\AppointmentTypeResource;
use App\Jobs\SendAppointmentMails;
use App\Jobs\SendPushNotification;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Services\BookingCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Zap\Exceptions\ScheduleConflictException;

/** La prise de rendez-vous côté visiteur : choix du type, du créneau, puis la demande. */
class AppointmentBookingController extends Controller
{
    public function __construct(private BookingCalendar $calendar) {}

    public function index(): Response
    {
        abort_unless($this->calendar->isOpen(), 404);

        return Inertia::render('public/appointments', [
            'types' => AppointmentTypeResource::collection($this->activeTypes()->get()),
        ]);
    }

    /**
     * Les créneaux libres d'un type de rendez-vous, jour par jour jusqu'à l'horizon.
     * Les heures sont en UTC (ISO 8601) : la page les affiche dans le fuseau du visiteur.
     */
    public function slots(Request $request): JsonResponse
    {
        abort_unless($this->calendar->isOpen(), 404);

        $type = $this->activeTypes()->findOrFail($request->integer('type'));
        $days = [];

        for ($day = CarbonImmutable::today(); $day->lte($this->calendar->lastBookableDay()); $day = $day->addDay()) {
            $starts = $this->calendar->availableStarts($type, $day);

            if ($starts !== []) {
                $days[] = [
                    'date' => $day->toDateString(),
                    'slots' => array_map(fn (CarbonImmutable $start): string => $start->utc()->toIso8601ZuluString(), $starts),
                ];
            }
        }

        return response()->json(['days' => $days]);
    }

    public function store(AppointmentBookingRequest $request): RedirectResponse
    {
        abort_unless($this->calendar->isOpen(), 404);

        $type = $this->activeTypes()->findOrFail($request->integer('appointment_type_id'));
        $startsAt = CarbonImmutable::parse($request->validated('starts_at'))->setTimezone(config('app.timezone'));

        // Le créneau a pu être pris (ou n'a jamais été proposé) depuis l'affichage de la page.
        if (! $this->calendar->isBookable($type, $startsAt)) {
            throw $this->slotTaken();
        }

        try {
            $appointment = DB::transaction(function () use ($request, $type, $startsAt): Appointment {
                $appointment = Appointment::query()->create([
                    ...$request->safe()->except(['website', 'starts_at']),
                    'appointment_type_id' => $type->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->addMinutes($type->duration_minutes),
                    'locale' => app()->getLocale(),
                    'status' => AppointmentStatus::Pending,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                $this->calendar->hold($appointment);

                return $appointment;
            });
        } catch (ScheduleConflictException) {
            throw $this->slotTaken();
        }

        SendAppointmentMails::dispatch($appointment->id, 'requested');
        SendPushNotification::dispatch('Demande de rendez-vous', "{$appointment->name} · {$startsAt->locale('fr')->isoFormat('ddd D MMM, HH:mm')}", ['type' => 'appointment', 'id' => (string) $appointment->id]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Merci ! Votre demande de rendez-vous est envoyée.')]);

        return back();
    }

    /** @return Builder<AppointmentType> */
    private function activeTypes(): Builder
    {
        return AppointmentType::query()->where('is_active', true)->orderBy('sort_order');
    }

    private function slotTaken(): ValidationException
    {
        return ValidationException::withMessages([
            'starts_at' => __('Ce créneau n’est plus disponible. Choisissez-en un autre.'),
        ]);
    }
}

<?php

namespace App\Services;

use App\Enums\AppointmentLocation;
use App\Enums\AppointmentStatus;
use App\Jobs\SendAppointmentMails;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\BookingSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Zap\Enums\ScheduleTypes;
use Zap\Facades\Zap;
use Zap\Models\Schedule;

/**
 * Mon agenda de rendez-vous, posé sur Zap (schedules des réglages de réservation) :
 * - mes disponibilités : des plages hebdomadaires (« lundi à vendredi, 9 h – 12 h ») ;
 * - mes périodes bloquées : congés, déplacements… (journées entières) ;
 * - les rendez-vous : un créneau bloqué tant que la demande est en attente ou confirmée.
 * Toutes les heures sont celles d'Abidjan (UTC, le fuseau de l'application).
 */
class BookingCalendar
{
    /** Jours de la semaine, dans l'ordre et au format attendu par Zap. */
    public const array DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    public function settings(): BookingSetting
    {
        return BookingSetting::current();
    }

    /** La réservation est ouverte et au moins un type de rendez-vous est proposé. */
    public function isOpen(): bool
    {
        return $this->settings()->is_enabled
            && AppointmentType::query()->where('is_active', true)->exists();
    }

    /**
     * Mes plages de disponibilité hebdomadaires.
     *
     * @return Collection<int, array{id: int, days: list<string>, start: string, end: string}>
     */
    public function availabilityRules(): Collection
    {
        return $this->settings()->availabilitySchedules()
            ->with('periods')
            ->oldest('id')
            ->get()
            ->map(fn (Schedule $schedule): array => [
                'id' => $schedule->id,
                'days' => array_values($schedule->metadata['days'] ?? []),
                'start' => substr((string) $schedule->periods->first()?->start_time, 0, 5),
                'end' => substr((string) $schedule->periods->first()?->end_time, 0, 5),
            ]);
    }

    /**
     * @param  list<string>  $days
     */
    public function addAvailability(array $days, string $start, string $end): Schedule
    {
        $days = array_values(array_intersect(self::DAYS, $days));

        return Zap::for($this->settings())
            ->named('Disponibilité')
            ->availability()
            ->from(today()->toDateString())
            ->addPeriod($start, $end)
            ->weekly($days)
            ->withMetadata(['days' => $days])
            ->save();
    }

    /**
     * Mes périodes bloquées qui ne sont pas encore passées.
     *
     * @return Collection<int, array{id: int, label: string|null, from: string, to: string}>
     */
    public function blockedPeriods(): Collection
    {
        return $this->settings()->blockedSchedules()
            ->where(fn ($query) => $query->whereNull('end_date')->orWhere('end_date', '>=', today()->toDateString()))
            ->orderBy('start_date')
            ->get()
            ->map(fn (Schedule $schedule): array => [
                'id' => $schedule->id,
                'label' => $schedule->description,
                'from' => $schedule->start_date->toDateString(),
                'to' => ($schedule->end_date ?? $schedule->start_date)->toDateString(),
            ]);
    }

    public function addBlockedPeriod(string $from, string $to, ?string $label = null): Schedule
    {
        $builder = Zap::for($this->settings())
            ->named('Indisponible')
            ->blocked()
            ->from($from)
            ->to($to)
            ->addPeriod('00:00', '23:59')
            ->daily();

        if (filled($label)) {
            $builder->description($label);
        }

        return $builder->save();
    }

    /** Retire une disponibilité ou une période bloquée (jamais le créneau d'un rendez-vous). */
    public function removeRule(Schedule $schedule): void
    {
        $settings = $this->settings();

        abort_unless(
            $schedule->schedulable_type === $settings->getMorphClass()
                && $schedule->schedulable_id === $settings->getKey()
                && ! $schedule->schedule_type->is(ScheduleTypes::APPOINTMENT),
            404,
        );

        $schedule->delete();
    }

    /**
     * Les débuts de créneaux encore libres pour ce type de rendez-vous, ce jour-là,
     * en tenant compte du délai minimum et de l'horizon de réservation.
     *
     * @return list<CarbonImmutable>
     */
    public function availableStarts(AppointmentType $type, CarbonImmutable $date): array
    {
        $settings = $this->settings();

        if ($date->startOfDay()->gt($this->lastBookableDay()) || $date->endOfDay()->lt(now())) {
            return [];
        }

        $earliest = now()->addHours($settings->min_notice_hours);

        return collect($settings->getBookableSlots($date->toDateString(), $type->duration_minutes, $settings->buffer_minutes))
            ->filter(fn (array $slot): bool => (bool) $slot['is_available'])
            ->map(fn (array $slot): CarbonImmutable => CarbonImmutable::parse($date->toDateString().' '.$slot['start_time']))
            ->filter(fn (CarbonImmutable $start): bool => $start->gte($earliest))
            ->values()
            ->all();
    }

    /** Ce créneau est-il encore proposé (le visiteur a pu attendre, ou trafiquer l'heure) ? */
    public function isBookable(AppointmentType $type, CarbonImmutable $start): bool
    {
        return collect($this->availableStarts($type, $start->startOfDay()))
            ->contains(fn (CarbonImmutable $slot): bool => $slot->equalTo($start));
    }

    public function lastBookableDay(): CarbonImmutable
    {
        return CarbonImmutable::today()->addDays($this->settings()->horizon_days);
    }

    /**
     * Bloque le créneau du rendez-vous dans l'agenda. Zap refuse un chevauchement
     * avec un autre rendez-vous ou une période bloquée (ScheduleConflictException).
     */
    public function hold(Appointment $appointment): void
    {
        $appointment->forceFill(['schedule_id' => $this->reserve($appointment)->id])->save();
    }

    /**
     * Réserve la plage du rendez-vous dans l'agenda, sans enregistrer le rendez-vous
     * (utile avant sa restauration). Lève ScheduleConflictException si elle est prise.
     */
    public function reserve(Appointment $appointment): Schedule
    {
        return Zap::for($this->settings())
            ->named('Rendez-vous #'.$appointment->id)
            ->appointment()
            ->from($appointment->starts_at->toDateString())
            ->addPeriod($appointment->starts_at->format('H:i'), $appointment->ends_at->format('H:i'))
            ->withMetadata(['appointment_id' => $appointment->id])
            ->save();
    }

    /** Libère le créneau (demande refusée, annulée ou supprimée). */
    public function release(Appointment $appointment): void
    {
        $appointment->schedule?->delete();
        $appointment->forceFill(['schedule_id' => null])->save();
    }

    /**
     * Confirme une demande en attente. Pour une visio sans détails précisés, mon
     * lien visio par défaut est repris.
     */
    public function confirm(Appointment $appointment, ?string $meetingDetails = null): void
    {
        abort_unless($appointment->status === AppointmentStatus::Pending, 409, __('Ce rendez-vous n’est plus en attente.'));

        if (blank($meetingDetails) && $appointment->location === AppointmentLocation::Video) {
            $meetingDetails = $this->settings()->video_link;
        }

        $appointment->update([
            'status' => AppointmentStatus::Confirmed,
            'meeting_details' => $meetingDetails,
            'confirmed_at' => now(),
        ]);

        SendAppointmentMails::dispatch($appointment->id, 'confirmed');
    }

    /** Refuse une demande (en attente ou déjà confirmée) et libère son créneau. */
    public function decline(Appointment $appointment, ?string $reason = null): void
    {
        abort_unless($appointment->status->holdsSlot(), 409, __('Ce rendez-vous est déjà refusé ou annulé.'));

        $appointment->update([
            'status' => AppointmentStatus::Declined,
            'decline_reason' => $reason,
        ]);
        $this->release($appointment);

        SendAppointmentMails::dispatch($appointment->id, 'declined');
    }

    /** Annulation par le visiteur (lien reçu par email) : le créneau est libéré. */
    public function cancel(Appointment $appointment): void
    {
        abort_unless($appointment->status->holdsSlot(), 409, __('Ce rendez-vous est déjà refusé ou annulé.'));

        $appointment->update([
            'status' => AppointmentStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
        $this->release($appointment);

        SendAppointmentMails::dispatch($appointment->id, 'cancelled');
    }
}

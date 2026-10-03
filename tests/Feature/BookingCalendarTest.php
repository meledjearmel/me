<?php

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\SiteSetting;
use App\Services\BookingCalendar;
use Carbon\CarbonImmutable;
use Zap\Exceptions\ScheduleConflictException;

beforeEach(function () {
    // Un lundi matin : mercredi est dans l'horizon, et au-delà du délai minimum.
    $this->travelTo(CarbonImmutable::parse('2026-10-05 08:00'));

    $this->settings = SiteSetting::factory()->bookable()->create([
        'booking_min_notice_hours' => 24,
        'booking_horizon_days' => 30,
    ]);
    $this->type = AppointmentType::factory()->create(['duration_minutes' => 60]);
    $this->calendar = app(BookingCalendar::class);
    $this->calendar->addAvailability(['monday', 'wednesday'], '09:00', '12:00');
});

/** @return list<string> */
function startTimes(array $starts): array
{
    return array_map(fn (CarbonImmutable $start): string => $start->format('H:i'), $starts);
}

test('slots follow the weekly availability', function () {
    $wednesday = CarbonImmutable::parse('2026-10-07');

    expect(startTimes($this->calendar->availableStarts($this->type, $wednesday)))
        ->toBe(['09:00', '10:00', '11:00'])
        ->and($this->calendar->availableStarts($this->type, CarbonImmutable::parse('2026-10-08')))->toBe([]);
});

test('slots too close in time or beyond the horizon are not offered', function () {
    // Lundi même : moins de 24 h de préavis.
    expect($this->calendar->availableStarts($this->type, CarbonImmutable::parse('2026-10-05')))->toBe([])
        ->and($this->calendar->availableStarts($this->type, CarbonImmutable::parse('2026-11-18')))->toBe([]);
});

test('a held appointment takes its slot, and releasing it frees the slot', function () {
    $wednesday = CarbonImmutable::parse('2026-10-07');
    $appointment = Appointment::factory()->create([
        'appointment_type_id' => $this->type->id,
        'starts_at' => '2026-10-07 10:00',
        'ends_at' => '2026-10-07 11:00',
    ]);

    $this->calendar->hold($appointment);

    expect(startTimes($this->calendar->availableStarts($this->type, $wednesday)))->toBe(['09:00', '11:00']);

    $this->calendar->release($appointment);

    expect(startTimes($this->calendar->availableStarts($this->type, $wednesday)))->toBe(['09:00', '10:00', '11:00'])
        ->and($appointment->fresh()->schedule_id)->toBeNull();
});

test('two appointments cannot hold the same slot', function () {
    $first = Appointment::factory()->create(['starts_at' => '2026-10-07 10:00', 'ends_at' => '2026-10-07 11:00']);
    $second = Appointment::factory()->create(['starts_at' => '2026-10-07 10:30', 'ends_at' => '2026-10-07 11:30']);

    $this->calendar->hold($first);
    $this->calendar->hold($second);
})->throws(ScheduleConflictException::class);

test('a blocked period removes the slots of those days', function () {
    $this->calendar->addBlockedPeriod('2026-10-07', '2026-10-09', 'Congés');

    expect($this->calendar->availableStarts($this->type, CarbonImmutable::parse('2026-10-07')))->toBe([])
        ->and(startTimes($this->calendar->availableStarts($this->type, CarbonImmutable::parse('2026-10-12'))))->toBe(['09:00', '10:00', '11:00'])
        ->and($this->calendar->blockedPeriods()->first())->toMatchArray(['label' => 'Congés', 'from' => '2026-10-07', 'to' => '2026-10-09']);
});

test('the buffer leaves a pause between two slots', function () {
    $this->settings->update(['booking_buffer_minutes' => 30]);

    expect(startTimes($this->calendar->availableStarts($this->type, CarbonImmutable::parse('2026-10-07'))))->toBe(['09:00', '10:30']);
});

test('availability rules can be listed', function () {
    expect($this->calendar->availabilityRules()->first())
        ->toMatchArray(['days' => ['monday', 'wednesday'], 'start' => '09:00', 'end' => '12:00']);
});

<?php

use App\Enums\AppointmentLocation;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\BookingSetting;
use App\Models\User;
use App\Services\BookingCalendar;
use Carbon\CarbonImmutable;
use Zap\Models\Schedule;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 08:00'));
    $this->user = User::factory()->create();
    BookingSetting::factory()->create(['video_link' => 'https://meet.example.test/armel']);
});

test('guests are redirected to the login page', function () {
    $this->get(route('admin.appointments.index'))->assertRedirect(route('login'));
    $this->get(route('admin.availability.index'))->assertRedirect(route('login'));
    $this->get(route('admin.appointment-types.index'))->assertRedirect(route('login'));
});

test('the booking pages render', function () {
    $appointment = Appointment::factory()->create();

    $this->actingAs($this->user)->get(route('admin.appointments.index'))->assertOk();
    $this->actingAs($this->user)->get(route('admin.appointments.show', $appointment))->assertOk();
    $this->actingAs($this->user)->get(route('admin.availability.index'))->assertOk();
    $this->actingAs($this->user)->get(route('admin.appointment-types.index'))->assertOk();
    $this->actingAs($this->user)->get(route('admin.appointment-types.create'))->assertOk();
});

test('an appointment type can be created with its locations', function () {
    $this->actingAs($this->user)->post(route('admin.appointment-types.store'), [
        'name' => ['fr' => 'Point projet', 'en' => 'Project review'],
        'description' => ['fr' => null, 'en' => null],
        'duration_minutes' => 60,
        'locations' => ['video', 'in_person'],
        'is_active' => '1',
        'sort_order' => 1,
    ])->assertRedirect(route('admin.appointment-types.index'));

    $type = AppointmentType::query()->firstOrFail();

    expect($type->getTranslation('name', 'fr'))->toBe('Point projet')
        ->and($type->locations->all())->toBe([AppointmentLocation::Video, AppointmentLocation::InPerson]);
});

test('an appointment type needs at least one known location', function () {
    $this->actingAs($this->user)->post(route('admin.appointment-types.store'), [
        'name' => ['fr' => 'Point', 'en' => 'Review'],
        'duration_minutes' => 30,
        'locations' => ['teleport'],
    ])->assertSessionHasErrors('locations.0');
});

test('settings, weekly ranges and blocked periods can be managed', function () {
    $this->actingAs($this->user)->patch(route('admin.availability.settings.update'), [
        'is_enabled' => '1',
        'min_notice_hours' => 12,
        'horizon_days' => 60,
        'buffer_minutes' => 10,
        'video_link' => 'https://meet.example.test/new',
    ])->assertSessionHasNoErrors();

    $this->actingAs($this->user)->post(route('admin.availability.rules.store'), [
        'days' => ['monday', 'friday'],
        'start' => '09:00',
        'end' => '12:00',
    ])->assertSessionHasNoErrors();

    $this->actingAs($this->user)->post(route('admin.availability.blocked-periods.store'), [
        'from' => '2026-10-10',
        'to' => '2026-10-12',
        'label' => 'Congés',
    ])->assertSessionHasNoErrors();

    $calendar = app(BookingCalendar::class);

    expect(BookingSetting::current()->only(['is_enabled', 'min_notice_hours', 'horizon_days']))->toBe(['is_enabled' => true, 'min_notice_hours' => 12, 'horizon_days' => 60])
        ->and($calendar->availabilityRules())->toHaveCount(1)
        ->and($calendar->blockedPeriods())->toHaveCount(1);

    $this->actingAs($this->user)->delete(route('admin.availability.destroy', $calendar->availabilityRules()->first()['id']));

    expect($calendar->availabilityRules())->toHaveCount(0);
});

test('a weekly range must end after it starts', function () {
    $this->actingAs($this->user)->post(route('admin.availability.rules.store'), [
        'days' => ['monday'],
        'start' => '12:00',
        'end' => '09:00',
    ])->assertSessionHasErrors('end');
});

test('the slot of an appointment cannot be removed from the availability page', function () {
    $appointment = Appointment::factory()->create();
    app(BookingCalendar::class)->hold($appointment);

    $this->actingAs($this->user)
        ->delete(route('admin.availability.destroy', $appointment->fresh()->schedule_id))
        ->assertNotFound();

    expect(Schedule::query()->count())->toBe(1);
});

test('confirming a video appointment uses the default video link', function () {
    $appointment = Appointment::factory()->create(['location' => AppointmentLocation::Video]);

    $this->actingAs($this->user)->patch(route('admin.appointments.confirm', $appointment), [
        'meeting_details' => '',
    ])->assertSessionHasNoErrors();

    expect($appointment->fresh())
        ->status->toBe(AppointmentStatus::Confirmed)
        ->meeting_details->toBe('https://meet.example.test/armel')
        ->confirmed_at->not->toBeNull();
});

test('declining an appointment frees its slot', function () {
    $appointment = Appointment::factory()->create();
    app(BookingCalendar::class)->hold($appointment);

    $this->actingAs($this->user)->patch(route('admin.appointments.decline', $appointment), [
        'decline_reason' => 'Je suis en déplacement.',
    ])->assertSessionHasNoErrors();

    expect($appointment->fresh())
        ->status->toBe(AppointmentStatus::Declined)
        ->decline_reason->toBe('Je suis en déplacement.')
        ->schedule_id->toBeNull()
        ->and(Schedule::query()->count())->toBe(0);
});

test('an appointment that is no longer pending cannot be confirmed', function () {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Cancelled]);

    $this->actingAs($this->user)->patch(route('admin.appointments.confirm', $appointment))->assertStatus(409);
});

test('a restored appointment takes its slot back when it is still free', function () {
    $appointment = Appointment::factory()->create();
    app(BookingCalendar::class)->hold($appointment);
    $this->actingAs($this->user)->delete(route('admin.appointments.destroy', $appointment));

    $this->actingAs($this->user)->patch(route('admin.trash.restore', ['appointments', $appointment->id]));

    expect($appointment->fresh()->schedule_id)->not->toBeNull()
        ->and(Schedule::query()->count())->toBe(1);
});

test('a restored appointment stays without a slot when the range is taken', function () {
    $appointment = Appointment::factory()->create();
    app(BookingCalendar::class)->hold($appointment);
    $this->actingAs($this->user)->delete(route('admin.appointments.destroy', $appointment));

    $other = Appointment::factory()->create(['starts_at' => $appointment->starts_at, 'ends_at' => $appointment->ends_at]);
    app(BookingCalendar::class)->hold($other);

    $this->actingAs($this->user)->patch(route('admin.trash.restore', ['appointments', $appointment->id]));

    expect($appointment->fresh())
        ->deleted_at->toBeNull()
        ->schedule_id->toBeNull()
        ->and(Schedule::query()->count())->toBe(1);
});

test('deleting an appointment frees its slot', function () {
    $appointment = Appointment::factory()->create();
    app(BookingCalendar::class)->hold($appointment);

    $this->actingAs($this->user)->delete(route('admin.appointments.destroy', $appointment))
        ->assertRedirect(route('admin.appointments.index'));

    expect(Schedule::query()->count())->toBe(0)
        ->and(Appointment::onlyTrashed()->count())->toBe(1);
});

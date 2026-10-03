<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\BookingSetting;
use App\Models\User;
use App\Services\BookingCalendar;
use Carbon\CarbonImmutable;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 08:00'));
    Sanctum::actingAs(User::factory()->create());
    BookingSetting::factory()->create();
});

test('la liste des rendez-vous est paginée et filtrable par statut', function () {
    Appointment::factory()->count(2)->create();
    Appointment::factory()->confirmed()->create();

    $this->getJson(route('api.v1.appointments.index', ['status' => 'confirmed']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'confirmed')
        ->assertJsonStructure(['data' => [['appointment_type' => ['name', 'duration_minutes']]], 'links', 'meta']);
});

test('un rendez-vous peut être confirmé puis refusé, ce qui libère son créneau', function () {
    $appointment = Appointment::factory()->create();
    app(BookingCalendar::class)->hold($appointment);

    $this->postJson(route('api.v1.appointments.confirm', $appointment), ['meeting_details' => 'Bureau, Cocody'])
        ->assertOk()
        ->assertJsonPath('status', 'confirmed')
        ->assertJsonPath('meeting_details', 'Bureau, Cocody');

    $this->postJson(route('api.v1.appointments.decline', $appointment))
        ->assertOk()
        ->assertJsonPath('status', 'declined');

    expect($appointment->fresh()->schedule_id)->toBeNull();
});

test('un rendez-vous annulé ne peut plus être confirmé', function () {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Cancelled]);

    $this->postJson(route('api.v1.appointments.confirm', $appointment))->assertStatus(409);
});

test('les types de rendez-vous se gèrent par l’API', function () {
    $this->postJson(route('api.v1.appointment-types.store'), [
        'name' => ['fr' => 'Appel', 'en' => 'Call'],
        'duration_minutes' => 30,
        'locations' => ['phone', 'whatsapp'],
    ])->assertCreated()->assertJsonPath('locations', ['phone', 'whatsapp']);

    $type = AppointmentType::query()->firstOrFail();

    $this->deleteJson(route('api.v1.appointment-types.destroy', $type))->assertNoContent();
});

test('les disponibilités se lisent et se modifient par l’API', function () {
    $this->postJson(route('api.v1.availability.rules.store'), ['days' => ['tuesday'], 'start' => '14:00', 'end' => '18:00'])
        ->assertCreated()
        ->assertJsonPath('rules.0.days', ['tuesday'])
        ->assertJsonPath('rules.0.start', '14:00');

    $this->postJson(route('api.v1.availability.blocked-periods.store'), ['from' => '2026-10-20', 'to' => '2026-10-21'])
        ->assertCreated()
        ->assertJsonPath('blocked_periods.0.from', '2026-10-20');

    $this->patchJson(route('api.v1.availability.settings.update'), [
        'is_enabled' => true,
        'min_notice_hours' => 48,
        'horizon_days' => 15,
        'buffer_minutes' => 0,
        'video_link' => null,
    ])->assertOk()->assertJsonPath('settings.min_notice_hours', 48);

    $this->getJson(route('api.v1.availability.index'))
        ->assertOk()
        ->assertJsonStructure(['settings', 'rules', 'blocked_periods']);
});

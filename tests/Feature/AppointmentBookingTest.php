<?php

use App\Enums\AppointmentLocation;
use App\Enums\AppointmentStatus;
use App\Jobs\SendAppointmentMails;
use App\Jobs\SendPushNotification;
use App\Mail\AppointmentReceivedMail;
use App\Mail\AppointmentVisitorMail;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Services\AppointmentInvite;
use App\Services\BookingCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    // Lundi 5 octobre, 8 h : mercredi 7 est réservable (délai de 24 h).
    $this->travelTo(CarbonImmutable::parse('2026-10-05 08:00'));
    Profile::factory()->create(['email' => 'armel@example.test']);
    SiteSetting::factory()->bookable()->create();
    $this->type = AppointmentType::factory()->create([
        'duration_minutes' => 60,
        'locations' => [AppointmentLocation::Video, AppointmentLocation::Phone],
    ]);
    app(BookingCalendar::class)->addAvailability(['wednesday'], '09:00', '12:00');
});

/** @return array<string, mixed> */
function booking(array $overrides = []): array
{
    return [
        'appointment_type_id' => AppointmentType::query()->value('id'),
        'location' => 'video',
        'starts_at' => '2026-10-07T10:00:00Z',
        'timezone' => 'Europe/Paris',
        'name' => 'Sam Client',
        'email' => 'sam@example.test',
        'company' => 'Acme',
        'message' => 'Parlons de mon application.',
        ...$overrides,
    ];
}

test('the booking page is hidden while booking is closed', function () {
    SiteSetting::current()->update(['booking_enabled' => false]);

    $this->get('/fr/appointments')->assertNotFound();
});

test('the booking page lists the active types', function () {
    AppointmentType::factory()->inactive()->create();

    $this->get('/fr/appointments')->assertInertia(fn ($page) => $page
        ->component('public/appointments')
        ->has('types', 1)
        ->where('types.0.name', 'Appel découverte')
        ->where('bookingOpen', true));
});

test('the free slots of a type are listed day by day in UTC', function () {
    $this->getJson('/fr/appointments/slots?type='.$this->type->id)
        ->assertOk()
        ->assertJsonPath('days.0.date', '2026-10-07')
        ->assertJsonPath('days.0.slots', ['2026-10-07T09:00:00Z', '2026-10-07T10:00:00Z', '2026-10-07T11:00:00Z']);
});

test('a visitor books a free slot: it is held and the emails are queued', function () {
    Queue::fake();

    $this->post('/fr/appointments', booking())->assertSessionHasNoErrors()->assertRedirect();

    $appointment = Appointment::query()->firstOrFail();

    expect($appointment)
        ->status->toBe(AppointmentStatus::Pending)
        ->starts_at->toDateTimeString()->toBe('2026-10-07 10:00:00')
        ->ends_at->toDateTimeString()->toBe('2026-10-07 11:00:00')
        ->timezone->toBe('Europe/Paris')
        ->schedule_id->not->toBeNull();
    Queue::assertPushed(SendAppointmentMails::class, fn (SendAppointmentMails $job) => $job->event === 'requested');
    Queue::assertPushed(SendPushNotification::class);

    $this->getJson('/fr/appointments/slots?type='.$this->type->id)
        ->assertJsonPath('days.0.slots', ['2026-10-07T09:00:00Z', '2026-10-07T11:00:00Z']);
});

test('a slot that is taken or never offered is refused', function (string $startsAt) {
    Queue::fake();
    $this->post('/fr/appointments', booking(['email' => 'first@example.test']));

    $this->post('/fr/appointments', booking(['starts_at' => $startsAt]))->assertSessionHasErrors('starts_at');

    expect(Appointment::query()->count())->toBe(1);
})->with([
    'already taken' => '2026-10-07T10:00:00Z',
    'outside the availability' => '2026-10-07T15:00:00Z',
    'too soon' => '2026-10-05T09:00:00Z',
]);

test('a phone appointment needs a phone number, and the location must be offered', function () {
    $this->post('/fr/appointments', booking(['location' => 'phone']))->assertSessionHasErrors('phone');
    $this->post('/fr/appointments', booking(['location' => 'in_person']))->assertSessionHasErrors('location');
});

test('the honeypot rejects bots', function () {
    $this->post('/fr/appointments', booking(['website' => 'https://spam.test']))->assertSessionHasErrors('website');

    expect(Appointment::query()->count())->toBe(0);
});

test('the request emails go to the visitor and to me', function () {
    Mail::fake();
    $appointment = Appointment::factory()->create(['appointment_type_id' => $this->type->id, 'email' => 'sam@example.test']);

    (new SendAppointmentMails($appointment->id, 'requested'))->handle();

    Mail::assertSent(AppointmentVisitorMail::class, fn (AppointmentVisitorMail $mail) => $mail->hasTo('sam@example.test') && $mail->kind === 'requested');
    Mail::assertSent(AppointmentReceivedMail::class, fn (AppointmentReceivedMail $mail) => $mail->hasTo('armel@example.test'));
});

test('the confirmation email carries a calendar invitation in the visitor time zone', function () {
    $appointment = Appointment::factory()->confirmed()->create([
        'appointment_type_id' => $this->type->id,
        'starts_at' => '2026-10-07 10:00',
        'ends_at' => '2026-10-07 11:00',
        'timezone' => 'Europe/Paris',
    ]);

    $mail = new AppointmentVisitorMail($appointment, 'confirmed');

    $mail->assertSeeInHtml('mercredi 7 octobre 2026, 12:00 – 13:00')
        ->assertSeeInHtml('https://meet.example.test/abc')
        ->assertHasAttachedData(app(AppointmentInvite::class)->ics($appointment), 'rendez-vous.ics', ['mime' => 'text/calendar; charset=utf-8; method=REQUEST']);
});

test('confirming or declining from the admin emails the visitor', function () {
    Queue::fake();
    $appointment = Appointment::factory()->create();

    app(BookingCalendar::class)->confirm($appointment);
    app(BookingCalendar::class)->decline($appointment->fresh());

    Queue::assertPushed(SendAppointmentMails::class, fn (SendAppointmentMails $job) => $job->event === 'confirmed');
    Queue::assertPushed(SendAppointmentMails::class, fn (SendAppointmentMails $job) => $job->event === 'declined');
});

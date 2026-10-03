<?php

use App\Enums\AppointmentStatus;
use App\Jobs\SendAppointmentMails;
use App\Mail\AppointmentReceivedMail;
use App\Mail\AppointmentVisitorMail;
use App\Models\Appointment;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Services\BookingCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Zap\Models\Schedule;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 08:00'));
    Profile::factory()->create(['email' => 'armel@example.test']);
    SiteSetting::factory()->bookable()->create();
});

test('the cancellation page shows the appointment from its link', function () {
    $appointment = Appointment::factory()->create();

    $this->get("/fr/appointments/{$appointment->cancel_token}/cancel")
        ->assertInertia(fn ($page) => $page
            ->component('public/appointment-cancel')
            ->where('appointment.cancellable', true)
            ->where('appointment.cancelled', false));
});

test('an unknown link is a 404', function () {
    $this->get('/fr/appointments/nope/cancel')->assertNotFound();
});

test('the visitor cancels: the slot is freed and everyone is told', function () {
    Queue::fake();
    $appointment = Appointment::factory()->confirmed()->create();
    app(BookingCalendar::class)->hold($appointment);

    $this->post("/fr/appointments/{$appointment->cancel_token}/cancel")->assertRedirect();

    expect($appointment->fresh())
        ->status->toBe(AppointmentStatus::Cancelled)
        ->cancelled_at->not->toBeNull()
        ->schedule_id->toBeNull()
        ->and(Schedule::query()->count())->toBe(0);
    Queue::assertPushed(SendAppointmentMails::class, fn (SendAppointmentMails $job) => $job->event === 'cancelled');
});

test('a past or already cancelled appointment cannot be cancelled', function () {
    $past = Appointment::factory()->create(['starts_at' => '2026-10-01 10:00', 'ends_at' => '2026-10-01 10:30']);
    $cancelled = Appointment::factory()->create(['status' => AppointmentStatus::Cancelled]);

    $this->post("/fr/appointments/{$past->cancel_token}/cancel")->assertStatus(409);
    $this->post("/fr/appointments/{$cancelled->cancel_token}/cancel")->assertStatus(409);
});

test('the cancellation emails go to the visitor and to me, with the visitor email carrying no cancel link', function () {
    Mail::fake();
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Cancelled, 'email' => 'sam@example.test']);

    (new SendAppointmentMails($appointment->id, 'cancelled'))->handle();

    Mail::assertSent(AppointmentVisitorMail::class, fn (AppointmentVisitorMail $mail) => $mail->kind === 'cancelled');
    Mail::assertSent(AppointmentReceivedMail::class, fn (AppointmentReceivedMail $mail) => $mail->hasTo('armel@example.test') && $mail->event === 'cancelled');

    (new AppointmentVisitorMail($appointment, 'cancelled'))->assertDontSeeInHtml('/cancel');
});

test('the request email carries the cancel link', function () {
    $appointment = Appointment::factory()->create();

    (new AppointmentVisitorMail($appointment, 'requested'))
        ->assertSeeInHtml("/fr/appointments/{$appointment->cancel_token}/cancel");
});

test('confirmed appointments of the next 24 hours are reminded once', function () {
    Queue::fake();
    $tomorrow = Appointment::factory()->confirmed()->create(['starts_at' => '2026-10-06 07:00', 'ends_at' => '2026-10-06 07:30']);
    Appointment::factory()->confirmed()->create(['starts_at' => '2026-10-08 10:00', 'ends_at' => '2026-10-08 10:30']);
    Appointment::factory()->create(['starts_at' => '2026-10-05 15:00', 'ends_at' => '2026-10-05 15:30']);

    $this->artisan('appointments:send-reminders')->assertSuccessful();
    $this->artisan('appointments:send-reminders')->assertSuccessful();

    Queue::assertPushed(SendAppointmentMails::class, 1);
    Queue::assertPushed(SendAppointmentMails::class, fn (SendAppointmentMails $job) => $job->event === 'reminder' && $job->appointmentId === $tomorrow->id);
    expect($tomorrow->fresh()->reminded_at)->not->toBeNull();
});

test('the shortcuts know whether booking is open', function () {
    $this->get('/fr')->assertInertia(fn ($page) => $page->where('bookingOpen', false));
});

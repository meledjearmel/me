<?php

use App\Enums\AppointmentLocation;
use App\Mail\AppointmentVisitorMail;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\AppointmentInvite;
use App\Services\BookingCalendar;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    Profile::factory()->create(['name' => 'Armel Meledje']);
});

test('confirming a video appointment creates a unique Jitsi room by default', function () {
    SiteSetting::factory()->create(['booking_video_provider' => 'jitsi']);
    $type = AppointmentType::factory()->create(['name' => ['fr' => 'Appel découverte', 'en' => 'Discovery call']]);
    $first = Appointment::factory()->create(['appointment_type_id' => $type->id, 'location' => AppointmentLocation::Video]);
    $second = Appointment::factory()->create(['appointment_type_id' => $type->id, 'location' => AppointmentLocation::Video]);

    app(BookingCalendar::class)->confirm($first);
    app(BookingCalendar::class)->confirm($second);

    expect($first->fresh()->meeting_details)->toMatch('#^https://meet\.jit\.si/armel-meledje-appel-decouverte-[a-z0-9]{12}$#')
        ->and($second->fresh()->meeting_details)->not->toBe($first->fresh()->meeting_details);
});

test('the fixed link is used when chosen, and details typed by hand always win', function () {
    SiteSetting::factory()->create(['booking_video_provider' => 'link', 'booking_video_link' => 'https://meet.example.test/armel']);
    $fixed = Appointment::factory()->create(['location' => AppointmentLocation::Video]);
    $typed = Appointment::factory()->create(['location' => AppointmentLocation::Video]);

    app(BookingCalendar::class)->confirm($fixed);
    app(BookingCalendar::class)->confirm($typed, 'https://zoom.example.test/42');

    expect($fixed->fresh()->meeting_details)->toBe('https://meet.example.test/armel')
        ->and($typed->fresh()->meeting_details)->toBe('https://zoom.example.test/42');
});

test('a phone appointment gets no video room', function () {
    SiteSetting::factory()->create(['booking_video_provider' => 'jitsi']);
    $appointment = Appointment::factory()->create(['location' => AppointmentLocation::Phone]);

    app(BookingCalendar::class)->confirm($appointment);

    expect($appointment->fresh()->meeting_details)->toBeNull();
});

test('the confirmation email and the calendar invitation carry the room link', function () {
    $appointment = Appointment::factory()->confirmed()->create(['meeting_details' => 'https://meet.jit.si/armel-meledje-appel-abc123']);

    (new AppointmentVisitorMail($appointment, 'confirmed'))
        ->assertSeeInHtml('Rejoindre la visio')
        ->assertSeeInHtml('https://meet.jit.si/armel-meledje-appel-abc123');

    expect(app(AppointmentInvite::class)->ics($appointment))->toContain('URL:https://meet.jit.si/armel-meledje-appel-abc123');
});

test('the video mode is set from the admin and needs a link when fixed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('admin.site-settings.update'), ['booking_video_provider' => 'link', 'booking_video_link' => ''])
        ->assertSessionHasErrors('booking_video_link');

    $this->actingAs($user)->patch(route('admin.site-settings.update'), ['booking_video_provider' => 'jitsi'])
        ->assertSessionHasNoErrors();

    expect(SiteSetting::current()->booking_video_provider)->toBe('jitsi');
});

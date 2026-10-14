<?php

use App\Enums\AppointmentStatus;
use App\Enums\CommentStatus;
use App\Enums\ContactStatus;
use App\Enums\EngagementStatus;
use App\Enums\TestimonialStatus;
use App\Mcp\Servers\PortfolioServer;
use App\Mcp\Tools\DecideAppointmentTool;
use App\Mcp\Tools\GetInboxTool;
use App\Mcp\Tools\GetStatsTool;
use App\Mcp\Tools\MarkHandledTool;
use App\Mcp\Tools\ModerateCommentTool;
use App\Mcp\Tools\ModerateTestimonialTool;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Engagement;
use App\Models\PageVisit;
use App\Models\PostComment;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\User;
use App\Services\BookingCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 08:00'));
    Mail::fake();
    Queue::fake();
    SiteSetting::factory()->bookable()->create();
    $this->server = PortfolioServer::actingAs(User::factory()->create());
});

test('get-inbox lists what awaits an answer, without emails or phones', function () {
    Contact::factory()->create(['name' => 'Awa', 'email' => 'awa@secret.test', 'status' => ContactStatus::New]);
    Contact::factory()->create(['name' => 'Déjà lu', 'status' => ContactStatus::Read]);
    Engagement::factory()->create(['name' => 'Globex', 'status' => EngagementStatus::New]);
    Appointment::factory()->create(['name' => 'Claire', 'phone' => '+41 79 000 00 00']);
    Testimonial::factory()->create(['author_name' => 'Marc', 'status' => TestimonialStatus::Pending]);
    PostComment::factory()->create(['author_name' => 'Lecteur', 'status' => CommentStatus::Pending]);

    $this->server->tool(GetInboxTool::class)
        ->assertOk()
        ->assertSee(['Awa', 'Globex', 'Claire', 'Marc', 'Lecteur'])
        ->assertDontSee(['awa@secret.test', '+41 79', 'Déjà lu']);
});

test('get-stats summarizes the period without the daily curve', function () {
    PageVisit::factory()->count(3)->create(['visitor_hash' => 'v', 'created_at' => now()->subDay()]);

    $this->server->tool(GetStatsTool::class, ['days' => 7])
        ->assertOk()
        ->assertSee('"period":3')
        ->assertSee('busiest_day')
        ->assertDontSee('"daily"');

    $this->server->tool(GetStatsTool::class, ['days' => 12])->assertHasErrors();
});

test('decide-appointment confirms then refuses a decided appointment again', function () {
    $appointment = Appointment::factory()->create();
    app(BookingCalendar::class)->hold($appointment);

    $this->server->tool(DecideAppointmentTool::class, ['id' => $appointment->id, 'decision' => 'confirm', 'meeting_details' => 'Visio : lien envoyé'])
        ->assertOk();

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Confirmed)
        ->and($appointment->meeting_details)->toBe('Visio : lien envoyé');

    $this->server->tool(DecideAppointmentTool::class, ['id' => $appointment->id, 'decision' => 'confirm'])->assertHasErrors();
});

test('testimonials and comments are moderated, and messages put away', function () {
    $testimonial = Testimonial::factory()->create(['status' => TestimonialStatus::Pending]);
    $comment = PostComment::factory()->create(['status' => CommentStatus::Pending]);
    $contact = Contact::factory()->create(['status' => ContactStatus::New]);
    $engagement = Engagement::factory()->create(['status' => EngagementStatus::New]);

    $this->server->tool(ModerateTestimonialTool::class, ['id' => $testimonial->id, 'status' => 'approved', 'is_featured' => true])->assertOk();
    $this->server->tool(ModerateCommentTool::class, ['id' => $comment->id, 'status' => 'rejected'])->assertOk();
    $this->server->tool(MarkHandledTool::class, ['type' => 'contact', 'id' => $contact->id, 'replied' => true])->assertOk();
    $this->server->tool(MarkHandledTool::class, ['type' => 'engagement', 'id' => $engagement->id])->assertOk();
    $this->server->tool(ModerateTestimonialTool::class, ['id' => $testimonial->id, 'status' => 'pending'])->assertHasErrors();

    expect($testimonial->refresh()->status)->toBe(TestimonialStatus::Approved)
        ->and($testimonial->is_featured)->toBeTrue()
        ->and($comment->refresh()->status)->toBe(CommentStatus::Rejected)
        ->and($contact->refresh()->status)->toBe(ContactStatus::Replied)
        ->and($engagement->refresh()->status)->toBe(EngagementStatus::Handled);
});

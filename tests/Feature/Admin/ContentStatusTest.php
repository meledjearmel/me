<?php

use App\Enums\AppointmentStatus;
use App\Enums\CommercialDocumentStatus;
use App\Enums\ContactStatus;
use App\Enums\ProjectStatus;
use App\Enums\PublicationStatus;
use App\Enums\RevisionStatus;
use App\Enums\TestimonialStatus;
use App\Jobs\SendAppointmentMails;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Celebration;
use App\Models\CommercialDocument;
use App\Models\Contact;
use App\Models\ContentRevision;
use App\Models\Domain;
use App\Models\EstimatorItem;
use App\Models\Experience;
use App\Models\Post;
use App\Models\Project;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

test('a project status is changed from its page', function () {
    $project = Project::factory()->create(['status' => ProjectStatus::Published]);

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.content-status.update', ['type' => 'project', 'id' => $project->id]), ['status' => 'archived'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($project->fresh()->status)->toBe(ProjectStatus::Archived);
});

test('a draft cannot be published while its english is missing', function () {
    $draft = Project::factory()->create(['status' => ProjectStatus::Draft, 'title' => ['fr' => 'Gestion de stock'], 'decisions' => [], 'key_figures' => []]);

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.content-status.update', ['type' => 'project', 'id' => $draft->id]), ['status' => 'published'])
        ->assertSessionHasErrors(['status' => "Complétez l'anglais avant de publier : title."]);

    expect($draft->fresh()->status)->toBe(ProjectStatus::Draft);
});

test('an experience with untranslated highlights stays a draft', function () {
    $experience = Experience::factory()->create(['status' => PublicationStatus::Draft]);
    $experience->highlights()->create(['text' => ['fr' => 'Livré en 3 mois'], 'sort_order' => 0]);

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.content-status.update', ['type' => 'experience', 'id' => $experience->id]), ['status' => 'published'])
        ->assertSessionHasErrors('status');
});

test('a published post without date appears right away', function () {
    $post = Post::factory()->draft()->create(['published_at' => null]);

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.content-status.update', ['type' => 'post', 'id' => $post->id]), ['status' => 'published']);

    expect($post->fresh()->status)->toBe(PublicationStatus::Published)
        ->and($post->fresh()->published_at)->not->toBeNull();
});

test('an unknown status or type is refused', function () {
    $project = Project::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.content-status.update', ['type' => 'project', 'id' => $project->id]), ['status' => 'deleted'])
        ->assertSessionHasErrors('status');

    $this->actingAs(User::factory()->create())
        ->patch('/admin/status/recipe/'.$project->id, ['status' => 'draft'])
        ->assertNotFound();
});

test('the mobile app changes a status', function () {
    Sanctum::actingAs(User::factory()->create());
    $project = Project::factory()->create(['status' => ProjectStatus::Published]);

    $this->patchJson(route('api.v1.content-status.update', ['type' => 'project', 'id' => $project->id]), ['status' => 'draft'])
        ->assertOk()
        ->assertJsonPath('data.status', 'draft');
});

test('a new item ordered 0 goes last, an explicit order is kept', function () {
    Domain::factory()->create(['sort_order' => 4]);

    expect(Domain::factory()->create(['sort_order' => 0])->sort_order)->toBe(5)
        ->and(Domain::factory()->create(['sort_order' => 2])->sort_order)->toBe(2)
        ->and(Domain::factory()->create(['sort_order' => null])->sort_order)->toBe(6);
});

test('a status is changed from a list row', function () {
    $contact = Contact::factory()->create(['status' => ContactStatus::New]);

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.content-status.update', ['type' => 'contact', 'id' => $contact->id]), ['status' => 'replied'])
        ->assertSessionHasNoErrors();

    expect($contact->fresh()->status)->toBe(ContactStatus::Replied);
});

test('a rejected testimonial leaves the featured ones', function () {
    $testimonial = Testimonial::factory()->create(['status' => TestimonialStatus::Approved, 'is_featured' => true]);

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.content-status.update', ['type' => 'testimonial', 'id' => $testimonial->id]), ['status' => 'rejected']);

    expect($testimonial->fresh())->status->toBe(TestimonialStatus::Rejected)->is_featured->toBeFalse();
});

test('confirming an appointment from the list mails the visitor', function () {
    Queue::fake();
    $appointment = Appointment::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.content-status.update', ['type' => 'appointment', 'id' => $appointment->id]), ['status' => 'confirmed'])
        ->assertSessionHasNoErrors();

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Confirmed);
    Queue::assertPushed(SendAppointmentMails::class, fn (SendAppointmentMails $job): bool => $job->event === 'confirmed');
});

test('issuing a document from the list gives it its number', function () {
    $document = CommercialDocument::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.content-status.update', ['type' => 'commercial_document', 'id' => $document->id]), ['status' => 'issued'])
        ->assertSessionHasNoErrors();

    expect($document->fresh())->status->toBe(CommercialDocumentStatus::Issued)->number->not->toBeNull();
});

test('a status that cannot follow the current one is refused', function (string $type, Closure $create, string $status) {
    $content = $create();
    $before = $content->status;

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.content-status.update', ['type' => $type, 'id' => $content->id]), ['status' => $status])
        ->assertSessionHasErrors(['status' => "Ce changement de statut n'est pas possible."]);

    expect($content->fresh()->status)->toBe($before);
})->with([
    'draft document marked paid' => ['commercial_document', fn () => CommercialDocument::factory()->create(), 'paid'],
    'declined appointment confirmed' => ['appointment', fn () => Appointment::factory()->create(['status' => AppointmentStatus::Declined]), 'confirmed'],
    'applied revision rejected' => ['revision', fn () => ContentRevision::factory()->create(['status' => RevisionStatus::Applied]), 'rejected'],
]);

test('a row with an active switch is hidden from its list', function (string $type, string $model) {
    $item = $model::factory()->create(['is_active' => true]);

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.content-status.update', ['type' => $type, 'id' => $item->id]), ['status' => 'hidden'])
        ->assertSessionHasNoErrors();

    expect($item->fresh()->is_active)->toBeFalse();
})->with([
    'estimator option' => ['estimator_item', EstimatorItem::class],
    'appointment type' => ['appointment_type', AppointmentType::class],
    'celebration' => ['celebration', Celebration::class],
]);

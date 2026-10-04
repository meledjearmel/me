<?php

use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ReviewInvitation;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Profile::factory()->create();
});

test('guests cannot manage review invitations', function () {
    $this->get(route('admin.review-invitations.index'))->assertRedirect(route('login'));
});

test('an invitation can be created with or without a subject and expires at the end of the chosen day', function () {
    $project = Project::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('admin.review-invitations.store'), [
            'name' => 'Sam Client',
            'locale' => 'en',
            'project_id' => $project->id,
            'expires_at' => now()->addWeek()->toDateString(),
        ])
        ->assertRedirect(route('admin.review-invitations.index'));

    $this->actingAs(User::factory()->create())
        ->post(route('admin.review-invitations.store'), ['locale' => 'fr'])
        ->assertSessionHasNoErrors();

    $invitation = ReviewInvitation::query()->where('name', 'Sam Client')->firstOrFail();

    expect($invitation->project_id)->toBe($project->id)
        ->and($invitation->expires_at->format('H:i'))->toBe('23:59')
        ->and($invitation->url)->toEndWith("/en/testimonials?invitation={$invitation->token}")
        ->and(ReviewInvitation::query()->whereNull('project_id')->exists())->toBeTrue();
});

test('the admin list gives the link to copy without exposing the raw token', function () {
    $invitation = ReviewInvitation::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.review-invitations.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/review-invitations/index')
            ->where('invitations.data.0.url', $invitation->url)
            ->where('invitations.data.0.status', 'pending')
            ->missing('invitations.data.0.token'));
});

test('the invitation link prefills the review form', function () {
    $experience = Experience::factory()->create(['role' => ['fr' => 'Développeur', 'en' => 'Developer'], 'company' => 'Acme']);
    $invitation = ReviewInvitation::factory()->create(['name' => 'Sam Client', 'email' => 'sam@example.test', 'experience_id' => $experience->id]);

    $this->get("/en/testimonials?invitation={$invitation->token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('reviewInvitation.valid', true)
            ->where('reviewInvitation.token', $invitation->token)
            ->where('reviewInvitation.name', 'Sam Client')
            ->where('reviewInvitation.email', 'sam@example.test')
            ->where('reviewInvitation.subject', 'Developer — Acme'));
});

test('a used, expired or unknown invitation link is reported as no longer valid', function (?string $state) {
    $token = $state === null ? 'unknown' : ReviewInvitation::factory()->{$state}()->create()->token;

    $this->get("/fr/testimonials?invitation={$token}")
        ->assertInertia(fn ($page) => $page->where('reviewInvitation', ['valid' => false]));
})->with(['used' => 'used', 'expired' => 'expired', 'unknown' => null]);

test('a review sent from an invitation takes its subject and uses up the link', function () {
    Queue::fake();
    $project = Project::factory()->create();
    $otherProject = Project::factory()->create();
    $invitation = ReviewInvitation::factory()->create(['project_id' => $project->id]);

    $this->post('/fr/testimonials', [
        'author_name' => 'Sam Client',
        'author_email' => 'sam@example.test',
        'content' => 'Un travail sérieux et une communication claire du début à la fin.',
        'project_id' => $otherProject->id,
        'invitation' => $invitation->token,
    ])->assertSessionHasNoErrors();

    $testimonial = Testimonial::query()->firstOrFail();
    $invitation->refresh();

    expect($testimonial->project_id)->toBe($project->id)
        ->and($invitation->used_at)->not->toBeNull()
        ->and($invitation->testimonial_id)->toBe($testimonial->id);
});

test('an invitation link cannot be used twice', function () {
    Queue::fake();
    $invitation = ReviewInvitation::factory()->used()->create();

    $this->post('/fr/testimonials', [
        'author_name' => 'Sam Client',
        'author_email' => 'sam@example.test',
        'content' => 'Un travail sérieux et une communication claire du début à la fin.',
        'invitation' => $invitation->token,
    ])->assertSessionHasErrors('invitation');

    expect(Testimonial::query()->count())->toBe(0);
});

test('invitations can be created and listed through the API', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/review-invitations', ['locale' => 'fr', 'email' => 'sam@example.test'])
        ->assertCreated()
        ->assertJsonPath('status', 'pending')
        ->assertJsonPath('subject', null)
        ->assertJsonMissingPath('token');

    $this->getJson('/api/v1/review-invitations?status=pending')
        ->assertOk()
        ->assertJsonPath('data.0.email', 'sam@example.test');
});

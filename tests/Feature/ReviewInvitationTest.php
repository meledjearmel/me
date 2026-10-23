<?php

use App\Models\Education;
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

function reviewPayload(array $overrides = []): array
{
    return [
        'author_name' => 'Sam Client',
        'author_email' => 'sam@example.test',
        'content' => 'Un travail sérieux et une communication claire du début à la fin.',
        ...$overrides,
    ];
}

test('guests cannot manage review invitations', function () {
    $this->get(route('admin.review-invitations.index'))->assertRedirect(route('login'));
});

test('an invitation can be linked to several subjects and expires at the end of the chosen day', function () {
    $projects = Project::factory()->count(2)->create();
    $experience = Experience::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('admin.review-invitations.store'), [
            'name' => 'Sam Client',
            'locale' => 'en',
            'project_ids' => $projects->modelKeys(),
            'experience_ids' => [$experience->id],
            'expires_at' => now()->addWeek()->toDateString(),
        ])
        ->assertRedirect(route('admin.review-invitations.index'));

    $this->actingAs(User::factory()->create())
        ->post(route('admin.review-invitations.store'), ['locale' => 'fr'])
        ->assertSessionHasNoErrors();

    $invitation = ReviewInvitation::query()->where('name', 'Sam Client')->firstOrFail();

    expect($invitation->projects->modelKeys())->toEqualCanonicalizing($projects->modelKeys())
        ->and($invitation->experiences->modelKeys())->toBe([$experience->id])
        ->and($invitation->max_uses)->toBe(1)
        ->and($invitation->expires_at->format('H:i'))->toBe('23:59')
        ->and($invitation->url)->toEndWith("/en/testimonials?invitation={$invitation->token}")
        ->and(ReviewInvitation::query()->whereDoesntHave('projects')->exists())->toBeTrue();
});

test('a collective invitation keeps its limit and prefills no one', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('admin.review-invitations.store'), [
            'name' => 'Équipe',
            'email' => 'team@example.test',
            'locale' => 'fr',
            'collective' => true,
            'max_uses' => 5,
        ])
        ->assertSessionHasNoErrors();

    $invitation = ReviewInvitation::query()->firstOrFail();

    expect($invitation->isCollective())->toBeTrue()
        ->and($invitation->max_uses)->toBe(5)
        ->and($invitation->name)->toBeNull()
        ->and($invitation->email)->toBeNull();
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

test('the invitation link prefills the review form with every subject', function () {
    $experience = Experience::factory()->create(['role' => ['fr' => 'Développeur', 'en' => 'Developer'], 'company' => 'Acme']);
    $education = Education::factory()->create(['degree' => ['fr' => 'Master', 'en' => 'Master'], 'institution' => 'ESATIC']);
    $invitation = ReviewInvitation::factory()->create(['name' => 'Sam Client', 'email' => 'sam@example.test']);
    $invitation->syncSubjects(['experience_ids' => [$experience->id], 'education_ids' => [$education->id]]);

    $this->get("/en/testimonials?invitation={$invitation->token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('reviewInvitation.valid', true)
            ->where('reviewInvitation.token', $invitation->token)
            ->where('reviewInvitation.name', 'Sam Client')
            ->where('reviewInvitation.email', 'sam@example.test')
            ->where('reviewInvitation.subject', 'Developer — Acme · Master — ESATIC'));
});

test('a used, expired or unknown invitation link is reported as no longer valid', function (?string $state) {
    $token = $state === null ? 'unknown' : ReviewInvitation::factory()->{$state}()->create()->token;

    $this->get("/fr/testimonials?invitation={$token}")
        ->assertInertia(fn ($page) => $page->where('reviewInvitation', ['valid' => false]));
})->with(['used' => 'used', 'expired' => 'expired', 'unknown' => null]);

test('a review sent from an invitation takes its subjects and uses up a personal link', function () {
    Queue::fake();
    $projects = Project::factory()->count(2)->create();
    $otherProject = Project::factory()->create();
    $invitation = ReviewInvitation::factory()->create();
    $invitation->projects()->attach($projects);

    $this->post('/fr/testimonials', reviewPayload([
        'project_id' => $otherProject->id,
        'invitation' => $invitation->token,
    ]))->assertSessionHasNoErrors();

    $testimonial = Testimonial::query()->firstOrFail();
    $invitation->refresh();

    expect($testimonial->projects->modelKeys())->toEqualCanonicalizing($projects->modelKeys())
        ->and($testimonial->review_invitation_id)->toBe($invitation->id)
        ->and($invitation->status)->toBe('used')
        ->and($invitation->uses_count)->toBe(1);
});

test('an invitation link cannot be used twice', function () {
    Queue::fake();
    $invitation = ReviewInvitation::factory()->used()->create();

    $this->post('/fr/testimonials', reviewPayload(['invitation' => $invitation->token]))
        ->assertSessionHasErrors('invitation');

    expect(Testimonial::query()->count())->toBe(0);
});

test('a collective link takes one review per person until its limit', function () {
    Queue::fake();
    $invitation = ReviewInvitation::factory()->collective(2)->create();

    $this->post('/fr/testimonials', reviewPayload(['invitation' => $invitation->token]))->assertSessionHasNoErrors();
    expect($invitation->refresh()->status)->toBe('pending');

    $this->post('/fr/testimonials', reviewPayload(['invitation' => $invitation->token]))
        ->assertSessionHasErrors('author_email');

    $this->post('/fr/testimonials', reviewPayload(['author_email' => 'alex@example.test', 'invitation' => $invitation->token]))
        ->assertSessionHasNoErrors();

    $this->post('/fr/testimonials', reviewPayload(['author_email' => 'lee@example.test', 'invitation' => $invitation->token]))
        ->assertSessionHasErrors('invitation');

    expect($invitation->refresh()->status)->toBe('used')
        ->and($invitation->testimonials()->count())->toBe(2);
});

test('invitations can be created and listed through the API', function () {
    Sanctum::actingAs(User::factory()->create());
    $project = Project::factory()->create();

    $this->postJson('/api/v1/review-invitations', ['locale' => 'fr', 'email' => 'sam@example.test'])
        ->assertCreated()
        ->assertJsonPath('status', 'pending')
        ->assertJsonPath('subject', null)
        ->assertJsonPath('collective', false)
        ->assertJsonMissingPath('token');

    $this->postJson('/api/v1/review-invitations', ['locale' => 'fr', 'collective' => true, 'project_id' => $project->id])
        ->assertCreated()
        ->assertJsonPath('collective', true)
        ->assertJsonPath('max_uses', null)
        ->assertJsonPath('subjects.0.id', $project->id);

    $this->getJson('/api/v1/review-invitations?status=pending')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

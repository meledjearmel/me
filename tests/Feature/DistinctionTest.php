<?php

use App\Enums\CongratulationSource;
use App\Enums\ProjectStatus;
use App\Enums\PublicationStatus;
use App\Models\Congratulation;
use App\Models\Counter;
use App\Models\Distinction;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Profile::factory()->create();
});

test('the about page lists published distinctions with a link to their published context', function () {
    $experience = Experience::factory()->create(['company' => 'CIAPOL']);
    Distinction::factory()->forContext($experience)->create([
        'title' => ['fr' => 'Meilleur agent', 'en' => 'Best agent'],
        'awarded_on' => '2025-06-30',
    ]);
    $project = Project::factory()->create(['status' => ProjectStatus::Draft]);
    // Le projet n'est pas publié : la distinction s'affiche sans lien vers lui.
    Distinction::factory()->forContext($project)->create(['awarded_on' => '2024-01-10']);
    Distinction::factory()->draft()->create();

    $this->get('/en/about')->assertOk()->assertInertia(fn ($page) => $page
        ->has('distinctions', 2)
        ->where('distinctions.0.title', 'Best agent')
        ->where('distinctions.0.context.label', 'CIAPOL')
        ->where('distinctions.0.context.url', route('about', ['locale' => 'en']).'#experience-'.$experience->id)
        ->where('distinctions.1.context', null));
});

test('an experience and an education show their own published distinctions', function () {
    $experience = Experience::factory()->create();
    $education = Education::factory()->create();
    Distinction::factory()->forContext($experience)->create(['title' => ['fr' => 'Prix agent', 'en' => 'Agent award']]);
    Distinction::factory()->forContext($experience)->draft()->create();
    Distinction::factory()->forContext($education)->create(['title' => ['fr' => 'Major de promotion', 'en' => 'Top of class']]);

    $this->get('/fr/about')->assertInertia(fn ($page) => $page
        ->has('experiences.0.distinctions', 1)
        ->where('experiences.0.distinctions.0.title', 'Prix agent')
        ->where('educations.0.distinctions.0.title', 'Major de promotion'));
});

test('a project page shows its published distinctions', function () {
    $project = Project::factory()->create(['status' => ProjectStatus::Published]);
    Distinction::factory()->forContext($project)->create(['title' => ['fr' => 'Prix innovation', 'en' => 'Innovation award']]);

    $this->get("/fr/projects/{$project->slug}")->assertOk()->assertInertia(fn ($page) => $page
        ->has('distinctions', 1)
        ->where('distinctions.0.title', 'Prix innovation'));
});

test('congratulating a distinction counts for it and for the shared total', function () {
    $distinction = Distinction::factory()->create(['congratulations_count' => 4]);

    $this->postJson("/fr/distinctions/{$distinction->id}/congratulations", ['count' => 3])
        ->assertOk()
        ->assertJson(['total' => 7]);

    expect($distinction->refresh()->congratulations_count)->toBe(7)
        ->and(Counter::total(Counter::CONGRATULATIONS))->toBe(3);

    $congratulation = Congratulation::query()->sole();
    expect($congratulation->source)->toBe(CongratulationSource::About)
        ->and($congratulation->distinction_id)->toBe($distinction->id)
        ->and($congratulation->reason)->toBe($distinction->getTranslation('title', 'fr'));
});

test('a draft distinction cannot be congratulated', function () {
    $distinction = Distinction::factory()->draft()->create();

    $this->postJson("/fr/distinctions/{$distinction->id}/congratulations", ['count' => 1])->assertNotFound();

    expect($distinction->refresh()->congratulations_count)->toBe(0);
});

test('a distinction is created with its context, detached, then sent to the trash from the admin', function () {
    $user = User::factory()->create();
    $education = Education::factory()->create();

    $this->actingAs($user)->post(route('admin.distinctions.store'), [
        'title' => ['fr' => 'Major de promotion', 'en' => 'Top of class'],
        'issuer' => 'ESATIC',
        'awarded_on' => '2019-07-01',
        'context_type' => 'education',
        'context_id' => $education->id,
        'sort_order' => 0,
    ])->assertRedirect(route('admin.distinctions.index'));

    $distinction = Distinction::query()->sole();
    expect($distinction->distinguishable->is($education))->toBeTrue()
        ->and($distinction->status)->toBe(PublicationStatus::Published);

    $this->actingAs($user)->put(route('admin.distinctions.update', $distinction), [
        'title' => ['fr' => 'Major de promotion', 'en' => 'Top of class'],
        'issuer' => 'ESATIC',
        'awarded_on' => '2019-07-01',
        'context_type' => null,
        'context_id' => null,
        'sort_order' => 1,
    ])->assertRedirect(route('admin.distinctions.index'));

    expect($distinction->refresh()->distinguishable_type)->toBeNull()
        ->and($distinction->distinguishable_id)->toBeNull();

    $this->actingAs($user)->delete(route('admin.distinctions.destroy', $distinction))->assertRedirect();
    $this->assertSoftDeleted($distinction);
});

test('the admin lists distinctions with their context and offers the contexts to choose from', function () {
    $user = User::factory()->create();
    $experience = Experience::factory()->create(['company' => 'CIAPOL']);
    $distinction = Distinction::factory()->forContext($experience)->create();

    $this->actingAs($user)->get(route('admin.distinctions.index'))->assertOk()->assertInertia(fn ($page) => $page
        ->component('admin/distinctions/index')
        ->where('distinctions.data.0.context_type', 'experience')
        ->where('distinctions.data.0.context_label', 'CIAPOL'));

    $this->actingAs($user)->get(route('admin.distinctions.edit', $distinction))->assertOk()->assertInertia(fn ($page) => $page
        ->component('admin/distinctions/edit')
        ->where('distinction.context_id', $experience->id)
        ->has('contexts.experience', 1));

    $this->actingAs($user)->get(route('admin.distinctions.create'))->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/distinctions/create'));
});

test('a distinction context must exist and name its type', function () {
    $this->actingAs(User::factory()->create())->post(route('admin.distinctions.store'), [
        'title' => ['fr' => 'Prix'],
        'issuer' => 'Organisme',
        'awarded_on' => now()->addDay()->toDateString(),
        'context_type' => 'project',
        'context_id' => 999,
    ])->assertSessionHasErrors(['title.en', 'awarded_on', 'context_id']);

    $this->actingAs(User::factory()->create())->post(route('admin.distinctions.store'), [
        'title' => ['fr' => 'Prix', 'en' => 'Award'],
        'issuer' => 'Organisme',
        'awarded_on' => '2024-01-01',
        'context_id' => 1,
    ])->assertSessionHasErrors(['context_type']);

    expect(Distinction::query()->count())->toBe(0);
});

test('the API creates a distinction linked to a project and exposes its context', function () {
    Sanctum::actingAs(User::factory()->create());
    $project = Project::factory()->create(['title' => ['fr' => 'App Station', 'en' => 'App Station']]);

    $this->postJson(route('api.v1.distinctions.store'), [
        'title' => ['fr' => 'Prix innovation', 'en' => 'Innovation award'],
        'issuer' => 'GITEX',
        'awarded_on' => '2026-02-01',
        'context_type' => 'project',
        'context_id' => $project->id,
    ])->assertCreated()
        ->assertJsonPath('context_type', 'project')
        ->assertJsonPath('context_id', $project->id)
        ->assertJsonPath('context_label', 'App Station');
});

test('the distinctions API requires authentication', function () {
    $this->getJson(route('api.v1.distinctions.index'))->assertUnauthorized();
});

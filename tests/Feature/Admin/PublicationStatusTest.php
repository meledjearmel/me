<?php

use App\Enums\PublicationStatus;
use App\Models\Domain;
use App\Models\Education;
use App\Models\Experience;
use App\Models\JobProfile;
use App\Models\Profile;
use App\Models\Skill;
use App\Models\User;

beforeEach(function () {
    Profile::factory()->create();
});

test('new content is published by default', function () {
    expect(Domain::factory()->create()->fresh()->status)->toBe(PublicationStatus::Published);
});

test('the admin lists can be filtered by publication status', function () {
    Domain::factory()->create(['status' => PublicationStatus::Draft]);
    Domain::factory()->count(2)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.domains.index', ['status' => 'draft']))
        ->assertInertia(fn ($page) => $page->has('domains.data', 1)->where('filters.status', 'draft'));
});

test('a status can be chosen when updating a domain, and only valid ones are accepted', function () {
    $domain = Domain::factory()->create();
    $payload = ['key' => $domain->key, 'label' => ['fr' => 'A', 'en' => 'A'], 'color' => '#fff', 'icon' => 'x', 'sort_order' => 1];

    $this->actingAs(User::factory()->create())
        ->put(route('admin.domains.update', $domain), [...$payload, 'status' => 'draft'])
        ->assertRedirect();
    expect($domain->fresh()->status)->toBe(PublicationStatus::Draft);

    $this->put(route('admin.domains.update', $domain), [...$payload, 'status' => 'archived'])
        ->assertSessionHasErrors('status');
});

test('drafts stay off the public pages', function () {
    $draftDomain = Domain::factory()->create(['status' => PublicationStatus::Draft]);
    $domain = Domain::factory()->create();
    Skill::factory()->create(['domain_id' => $domain->id, 'status' => PublicationStatus::Draft]);
    Skill::factory()->create(['domain_id' => $domain->id]);
    Skill::factory()->create(['domain_id' => $draftDomain->id]);
    JobProfile::factory()->create(['status' => PublicationStatus::Draft]);
    JobProfile::factory()->create();
    Experience::factory()->create(['status' => PublicationStatus::Draft]);
    Education::factory()->create(['status' => PublicationStatus::Draft]);

    $this->get('/fr/skills')->assertInertia(fn ($page) => $page
        ->has('domains', 1)
        ->has('skills', 1)
    );

    $this->get('/fr')->assertInertia(fn ($page) => $page->has('jobProfiles', 1));

    $this->get('/fr/about')->assertInertia(fn ($page) => $page
        ->has('experiences', 0)
        ->has('educations', 0)
    );
});

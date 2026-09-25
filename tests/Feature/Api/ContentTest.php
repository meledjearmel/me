<?php

use App\Enums\PublicationStatus;
use App\Enums\TechnologyCategory;
use App\Models\Domain;
use App\Models\Education;
use App\Models\Experience;
use App\Models\JobProfile;
use App\Models\ProfessionalReference;
use App\Models\Skill;
use App\Models\Technology;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create());
});

test('un domaine se crée, se lit, se modifie et se supprime', function () {
    $payload = [
        'key' => 'design',
        'label' => ['fr' => 'Design', 'en' => 'Design'],
        'color' => '#F472B6',
        'icon' => 'palette',
        'sort_order' => 1,
    ];

    $id = $this->postJson(route('api.v1.domains.store'), $payload)
        ->assertCreated()
        ->assertJsonPath('label.fr', 'Design')
        ->json('id');

    $this->getJson(route('api.v1.domains.show', $id))->assertOk()->assertJsonPath('key', 'design');

    $this->putJson(route('api.v1.domains.update', $id), [...$payload, 'label' => ['fr' => 'UX', 'en' => 'UX']])
        ->assertOk()
        ->assertJsonPath('label.fr', 'UX');

    $this->deleteJson(route('api.v1.domains.destroy', $id))->assertNoContent();
    expect(Domain::query()->count())->toBe(0);
});

test('un domaine refuse une clé déjà utilisée', function () {
    $domain = Domain::factory()->create();

    $this->postJson(route('api.v1.domains.store'), [
        'key' => $domain->key,
        'label' => ['fr' => 'A', 'en' => 'A'],
        'color' => '#000000',
        'icon' => 'x',
    ])->assertUnprocessable()->assertJsonValidationErrors('key');
});

test('la liste des domaines est paginée', function () {
    Domain::factory()->count(3)->create();

    $this->getJson(route('api.v1.domains.index'))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data', 'links', 'meta']);
});

test('une technologie se crée et se filtre par catégorie', function () {
    $category = TechnologyCategory::cases()[0];

    $this->postJson(route('api.v1.technologies.store'), ['name' => 'Laravel', 'category' => $category->value])
        ->assertCreated()
        ->assertJsonPath('category', $category->value);

    $this->getJson(route('api.v1.technologies.index', ['category' => $category->value]))
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->postJson(route('api.v1.technologies.store'), ['name' => 'X', 'category' => 'inconnue'])
        ->assertUnprocessable();
});

test('un profil métier se crée avec ses textes bilingues', function () {
    $this->postJson(route('api.v1.job-profiles.store'), [
        'key' => 'backend',
        'label' => ['fr' => 'Back-end', 'en' => 'Back-end'],
        'description' => ['fr' => 'Desc', 'en' => 'Desc'],
        'cv_description' => ['fr' => 'CV', 'en' => 'CV'],
    ])->assertCreated()->assertJsonPath('key', 'backend');

    $profile = JobProfile::factory()->create();

    $this->getJson(route('api.v1.job-profiles.show', $profile))->assertOk()->assertJsonPath('id', $profile->id);
});

test('une compétence garde l\'ordre de ses technologies', function () {
    $domain = Domain::factory()->create();
    [$first, $second] = Technology::factory()->count(2)->create();

    $response = $this->postJson(route('api.v1.skills.store'), [
        'domain_id' => $domain->id,
        'name' => ['fr' => 'Laravel', 'en' => 'Laravel'],
        'technologies' => [$second->id, $first->id],
    ])->assertCreated();

    expect(collect($response->json('technologies'))->pluck('id')->all())->toBe([$second->id, $first->id]);

    $skill = Skill::query()->firstOrFail();

    $this->putJson(route('api.v1.skills.update', $skill), [
        'domain_id' => $domain->id,
        'name' => ['fr' => 'Nouveau', 'en' => 'New'],
        'technologies' => [],
    ])->assertOk()->assertJsonCount(0, 'technologies');
});

test('les compétences se filtrent par domaine', function () {
    Skill::factory()->count(2)->create();
    $skill = Skill::query()->first();

    $this->getJson(route('api.v1.skills.index', ['domain_id' => $skill->domain_id]))
        ->assertOk()
        ->assertJsonPath('data.0.domain_id', $skill->domain_id);
});

test('une formation refuse une date de fin antérieure au début', function () {
    $this->postJson(route('api.v1.educations.store'), [
        'institution' => 'INP-HB',
        'degree' => ['fr' => 'Licence', 'en' => 'Bachelor'],
        'field' => ['fr' => 'Informatique', 'en' => 'Computer science'],
        'start_date' => '2020-09-01',
        'end_date' => '2019-06-30',
    ])->assertUnprocessable()->assertJsonValidationErrors('end_date');

    $education = Education::factory()->create();

    $this->deleteJson(route('api.v1.educations.destroy', $education))->assertNoContent();
});

test('une référence professionnelle se crée et se met à jour', function () {
    $id = $this->postJson(route('api.v1.professional-references.store'), [
        'name' => 'Jeanne Dupont',
        'is_public' => true,
        'visible_fields' => ['name', 'company'],
    ])->assertCreated()->assertJsonPath('is_public', true)->json('id');

    $this->putJson(route('api.v1.professional-references.update', $id), ['name' => 'Jeanne Martin'])
        ->assertOk()
        ->assertJsonPath('name', 'Jeanne Martin');

    $this->postJson(route('api.v1.professional-references.store'), [
        'name' => 'X',
        'visible_fields' => ['inconnu'],
    ])->assertUnprocessable()->assertJsonValidationErrors('visible_fields.0');

    expect(ProfessionalReference::query()->count())->toBe(1);
});

test('le contenu se publie ou se met en brouillon et se filtre par statut', function () {
    Domain::factory()->create(['status' => PublicationStatus::Published]);
    $draft = Domain::factory()->create(['status' => PublicationStatus::Draft]);

    $this->getJson(route('api.v1.domains.index', ['status' => 'draft']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $draft->id)
        ->assertJsonPath('data.0.status', 'draft');

    $this->putJson(route('api.v1.domains.update', $draft), [
        'key' => $draft->key,
        'label' => $draft->getTranslations('label'),
        'color' => $draft->color,
        'icon' => $draft->icon,
        'status' => 'published',
    ])->assertOk()->assertJsonPath('status', 'published');

    $this->putJson(route('api.v1.domains.update', $draft), [
        'key' => $draft->key,
        'label' => $draft->getTranslations('label'),
        'color' => $draft->color,
        'icon' => $draft->icon,
        'status' => 'archived',
    ])->assertUnprocessable()->assertJsonValidationErrors('status');
});

test('les formations, expériences, compétences et profils métier exposent leur statut', function () {
    Education::factory()->create();
    Experience::factory()->create();
    Skill::factory()->create();
    JobProfile::factory()->create();

    foreach (['educations', 'experiences', 'skills', 'job-profiles'] as $resource) {
        $this->getJson(route("api.v1.{$resource}.index", ['status' => 'draft']))->assertOk()->assertJsonCount(0, 'data');
        $this->getJson(route("api.v1.{$resource}.index", ['status' => 'published']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'published');
    }
});

<?php

use App\Models\Domain;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create());
});

/**
 * @return array<string, mixed>
 */
function projectPayload(Project $project, array $overrides = []): array
{
    return [
        'title' => $project->getTranslations('title'),
        'slug' => $project->slug,
        'context' => $project->getTranslations('context'),
        'realization' => $project->getTranslations('realization'),
        'result' => $project->getTranslations('result'),
        'status' => $project->status->value,
        'sort_order' => $project->sort_order,
        ...$overrides,
    ];
}

test('un projet se crée avec ses relations et sa couverture', function () {
    Storage::fake('public');
    $domain = Domain::factory()->create();

    $response = $this->post(route('api.v1.projects.store'), [
        'title' => ['fr' => 'Mon projet', 'en' => 'My project'],
        'slug' => 'mon-projet',
        'context' => ['fr' => 'Contexte', 'en' => 'Context'],
        'realization' => ['fr' => 'Réalisation', 'en' => 'Realization'],
        'result' => ['fr' => 'Résultat', 'en' => 'Result'],
        'status' => 'published',
        'domains' => [$domain->id],
        'cover' => UploadedFile::fake()->image('cover.jpg'),
    ], ['Accept' => 'application/json'])->assertCreated();

    $response->assertJsonPath('slug', 'mon-projet')->assertJsonCount(1, 'domains');
    expect($response->json('cover_url'))->not->toBeNull();
});

test('deux projets liés le sont dans les deux sens', function () {
    [$a, $b] = Project::factory()->count(2)->create();

    $this->putJson(route('api.v1.projects.update', $a), projectPayload($a, ['related_projects' => [$b->id]]))
        ->assertOk()
        ->assertJsonPath('related_project_ids', [$b->id]);

    $this->getJson(route('api.v1.projects.show', $b))->assertJsonPath('related_project_ids', [$a->id]);
});

test('un projet refuse un slug déjà pris mais accepte le sien', function () {
    [$a, $b] = Project::factory()->count(2)->create();

    $this->putJson(route('api.v1.projects.update', $a), projectPayload($a, ['slug' => $b->slug]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('slug');

    $this->putJson(route('api.v1.projects.update', $a), projectPayload($a))->assertOk();
});

test('les projets se filtrent par statut et se suppriment', function () {
    Project::factory()->create(['status' => 'archived']);
    $published = Project::factory()->create(['status' => 'published']);

    $this->getJson(route('api.v1.projects.index', ['status' => 'archived']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure(['data', 'links', 'meta']);

    $this->deleteJson(route('api.v1.projects.destroy', $published))->assertNoContent();
    expect(Project::query()->count())->toBe(1);
});

test('une expérience gère ses points marquants', function () {
    $payload = [
        'company' => 'Acme',
        'role' => ['fr' => 'Développeur', 'en' => 'Developer'],
        'start_date' => '2022-01-01',
        'highlights' => [
            ['text' => ['fr' => 'Un', 'en' => 'One'], 'sort_order' => 0],
            ['text' => ['fr' => 'Deux', 'en' => 'Two'], 'sort_order' => 1],
        ],
    ];

    $response = $this->postJson(route('api.v1.experiences.store'), $payload)
        ->assertCreated()
        ->assertJsonCount(2, 'highlights');

    $kept = $response->json('highlights.0');

    $this->putJson(route('api.v1.experiences.update', $response->json('id')), [
        ...$payload,
        'highlights' => [['id' => $kept['id'], 'text' => ['fr' => 'Un bis', 'en' => 'One bis'], 'sort_order' => 0]],
    ])->assertOk()
        ->assertJsonCount(1, 'highlights')
        ->assertJsonPath('highlights.0.id', $kept['id'])
        ->assertJsonPath('highlights.0.text.fr', 'Un bis');
});

test('une expérience refuse une fin antérieure au début', function () {
    $this->postJson(route('api.v1.experiences.store'), [
        'company' => 'Acme',
        'role' => ['fr' => 'Dev', 'en' => 'Dev'],
        'start_date' => '2022-01-01',
        'end_date' => '2021-01-01',
    ])->assertUnprocessable()->assertJsonValidationErrors('end_date');

    $experience = Experience::factory()->create();
    $this->deleteJson(route('api.v1.experiences.destroy', $experience))->assertNoContent();
});

test('le profil se lit et se modifie', function () {
    $profile = Profile::factory()->create();

    $this->getJson(route('api.v1.profile.show'))->assertOk()->assertJsonPath('id', $profile->id);

    $this->patchJson(route('api.v1.profile.update'), [
        'name' => 'Nouveau nom',
        'headline' => $profile->getTranslations('headline'),
        'bio_short' => $profile->getTranslations('bio_short'),
        'bio_full' => $profile->getTranslations('bio_full'),
        'email' => $profile->email,
    ])->assertOk()->assertJsonPath('name', 'Nouveau nom');

    $this->patchJson(route('api.v1.profile.update'), [])->assertUnprocessable();
});

test('les photos du profil s\'envoient en multipart', function () {
    Storage::fake('public');
    $profile = Profile::factory()->create();

    $response = $this->post(route('api.v1.profile.update'), [
        '_method' => 'PATCH',
        'name' => $profile->name,
        'headline' => $profile->getTranslations('headline'),
        'bio_short' => $profile->getTranslations('bio_short'),
        'bio_full' => $profile->getTranslations('bio_full'),
        'email' => $profile->email,
        'photo' => UploadedFile::fake()->image('site.jpg'),
    ], ['Accept' => 'application/json'])->assertOk();

    expect($response->json('photo_url'))->not->toBeNull();
});

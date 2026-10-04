<?php

use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\ProjectSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.projects.index'))->assertRedirect(route('login'));
});

test('authenticated users can create a project with relations and media', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $domain = Domain::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.projects.store'), [
        'title' => ['fr' => 'Mon projet', 'en' => 'My project'],
        'slug' => 'mon-projet',
        'context' => ['fr' => 'Contexte', 'en' => 'Context'],
        'realization' => ['fr' => 'Réalisation', 'en' => 'Realization'],
        'result' => ['fr' => 'Résultat', 'en' => 'Result'],
        'status' => 'published',
        'sort_order' => 1,
        'domains' => [$domain->id],
        'cover' => UploadedFile::fake()->image('cover.jpg'),
    ]);

    $response->assertRedirect(route('admin.projects.index'));
    $project = Project::query()->where('slug', 'mon-projet')->firstOrFail();
    expect($project->domains)->toHaveCount(1);
    expect($project->getFirstMediaUrl('cover'))->not->toBe('');
});

test('authenticated users can link two projects symmetrically', function () {
    $user = User::factory()->create();
    $projectA = Project::factory()->create();
    $projectB = Project::factory()->create();

    $this->actingAs($user)->put(route('admin.projects.update', $projectA), [
        'title' => $projectA->getTranslations('title'),
        'slug' => $projectA->slug,
        'context' => $projectA->getTranslations('context'),
        'realization' => $projectA->getTranslations('realization'),
        'result' => $projectA->getTranslations('result'),
        'status' => $projectA->status->value,
        'sort_order' => $projectA->sort_order,
        'related_projects' => [$projectB->id],
    ])->assertRedirect(route('admin.projects.index'));

    expect($projectA->relatedProjects()->pluck('projects.id')->all())->toBe([$projectB->id]);
    expect($projectB->relatedProjects()->pluck('projects.id')->all())->toBe([$projectA->id]);
});

test('authenticated users can delete a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $this->actingAs($user)->delete(route('admin.projects.destroy', $project))
        ->assertRedirect(route('admin.projects.index'));

    $this->assertSoftDeleted($project);
});

test('creating a project requires the mandatory fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.projects.store'), []);

    $response->assertSessionHasErrors(['title.fr', 'title.en', 'slug', 'context.fr', 'realization.fr', 'result.fr', 'status']);
});

test('authenticated users can remove the cover of a project', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->addMedia(UploadedFile::fake()->image('cover.jpg'))->toMediaCollection('cover');

    $this->actingAs($user)->delete(route('admin.projects.cover.destroy', $project))
        ->assertRedirect();

    expect($project->fresh()->getFirstMedia('cover'))->toBeNull();
});

test('authenticated users can remove one image from the gallery', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $kept = $project->addMedia(UploadedFile::fake()->image('a.jpg'))->toMediaCollection('gallery');
    $removed = $project->addMedia(UploadedFile::fake()->image('b.jpg'))->toMediaCollection('gallery');

    $this->actingAs($user)->delete(route('admin.projects.gallery.destroy', [$project, $removed]))
        ->assertRedirect();

    $project->refresh();
    expect($project->getMedia('gallery')->pluck('id')->all())->toBe([$kept->id]);
});

test('a gallery image cannot be removed through another project', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $otherProject = Project::factory()->create();
    $media = $otherProject->addMedia(UploadedFile::fake()->image('a.jpg'))->toMediaCollection('gallery');

    $this->actingAs($user)->delete(route('admin.projects.gallery.destroy', [$project, $media]))
        ->assertNotFound();

    expect($otherProject->fresh()->getMedia('gallery'))->toHaveCount(1);
});

test('authenticated users can save the case study details of a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $payload = fn (array $extra): array => [
        'title' => $project->getTranslations('title'),
        'slug' => $project->slug,
        'context' => $project->getTranslations('context'),
        'realization' => $project->getTranslations('realization'),
        'result' => $project->getTranslations('result'),
        'status' => $project->status->value,
        'sort_order' => $project->sort_order,
        ...$extra,
    ];

    $this->actingAs($user)->put(route('admin.projects.update', $project), $payload([
        'tagline' => ['fr' => 'Vendre ses logiciels', 'en' => 'Sell your software'],
        'role' => ['fr' => 'Développeur principal', 'en' => 'Lead developer'],
        'client' => ['fr' => 'Projet personnel', 'en' => 'Personal project'],
        'platform' => ['fr' => 'Web · API', 'en' => 'Web · API'],
        'key_figures' => [
            ['value' => '630+', 'label' => ['fr' => 'tests automatisés', 'en' => 'automated tests']],
        ],
    ]))->assertRedirect(route('admin.projects.index'));

    $project->refresh();
    expect($project->getTranslation('tagline', 'en'))->toBe('Sell your software');
    expect($project->key_figures)->toBe([
        ['value' => '630+', 'label' => ['fr' => 'tests automatisés', 'en' => 'automated tests']],
    ]);

    $this->actingAs($user)->put(route('admin.projects.update', $project), $payload([]));

    expect($project->refresh()->key_figures)->toBe([]);
});

test('authenticated users can save the challenges, technical choices and project sheet', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $decision = ['choice' => ['fr' => 'Inertia', 'en' => 'Inertia'], 'reason' => ['fr' => 'Une seule app', 'en' => 'One app']];

    $payload = fn (array $extra): array => [
        'title' => $project->getTranslations('title'),
        'slug' => $project->slug,
        'context' => $project->getTranslations('context'),
        'realization' => $project->getTranslations('realization'),
        'result' => $project->getTranslations('result'),
        'status' => $project->status->value,
        'sort_order' => $project->sort_order,
        ...$extra,
    ];

    $this->actingAs($user)->put(route('admin.projects.update', $project), $payload([
        'challenges' => ['fr' => 'Trois mois', 'en' => 'Three months'],
        'decisions' => [$decision],
        'started_on' => '2024-03',
        'ended_on' => '2024-06',
        'team_size' => 3,
    ]))->assertRedirect(route('admin.projects.index'));

    $project->refresh();
    expect($project->getTranslation('challenges', 'en'))->toBe('Three months')
        ->and($project->decisions)->toBe([$decision])
        ->and($project->started_on->toDateString())->toBe('2024-03-01')
        ->and($project->ended_on->toDateString())->toBe('2024-06-01')
        ->and($project->team_size)->toBe(3);

    // Le formulaire envoie toujours les champs de la fiche, vides une fois effacés.
    $this->actingAs($user)->put(route('admin.projects.update', $project), $payload([
        'started_on' => null,
        'ended_on' => null,
        'team_size' => null,
    ]));

    $project->refresh();
    expect($project->decisions)->toBe([])
        ->and($project->started_on)->toBeNull()
        ->and($project->team_size)->toBeNull();
});

test('a technical choice needs both texts in both languages and the end cannot precede the start', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('admin.projects.store'), [
        'decisions' => [['choice' => ['fr' => 'Inertia']]],
        'started_on' => '2024-06',
        'ended_on' => '2024-03',
    ])->assertSessionHasErrors(['decisions.0.choice.en', 'decisions.0.reason.fr', 'ended_on']);
});

test('a key figure needs a value and both labels', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('admin.projects.store'), [
        'key_figures' => [['value' => '', 'label' => ['fr' => 'tests']]],
    ])->assertSessionHasErrors(['key_figures.0.value', 'key_figures.0.label.en']);
});

test('filling the case studies completes empty fields without overwriting edited ones', function () {
    $edited = Project::factory()->create([
        'slug' => 'app-station',
        'tagline' => ['fr' => 'Mon accroche', 'en' => 'My tagline'],
    ]);
    $empty = Project::factory()->create(['slug' => 'registra']);

    (new ProjectSeeder)->fillCaseStudies();

    expect($edited->refresh()->getTranslation('tagline', 'fr'))->toBe('Mon accroche');
    expect($edited->key_figures)->toHaveCount(4);
    expect($empty->refresh()->getTranslation('tagline', 'fr'))->not->toBe('');
    expect($empty->key_figures[0]['value'])->toBe('126');
});

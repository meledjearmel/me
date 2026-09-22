<?php

use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
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

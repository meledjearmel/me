<?php

use App\Enums\ProjectStatus;
use App\Enums\PublicationStatus;
use App\Models\Domain;
use App\Models\Experience;
use App\Models\Post;
use App\Models\Project;
use App\Models\User;
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

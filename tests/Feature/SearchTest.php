<?php

use App\Enums\ProjectStatus;
use App\Models\PageVisit;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\Skill;

beforeEach(function () {
    Profile::factory()->create();
});

test('the search finds posts, projects and skills, ignoring accents and case', function () {
    $post = Post::factory()->create(['title' => ['fr' => 'Déployer Laravel sans stress'], 'slug' => 'deployer-laravel']);
    $project = Project::factory()->create(['title' => ['fr' => 'Plateforme LARAVEL'], 'slug' => 'plateforme']);
    $skill = Skill::factory()->create(['name' => ['fr' => 'Backend Laravel']]);

    $response = $this->getJson('/fr/search?q=laravel');

    $response->assertOk()
        ->assertJsonPath('results.0', ['type' => 'post', 'title' => $post->title, 'excerpt' => $post->excerpt ?: null, 'url' => '/fr/blog/deployer-laravel'])
        ->assertJsonPath('results.1.url', '/fr/projects/plateforme')
        ->assertJsonPath('results.2.url', "/fr/skills#domain-{$skill->domain->key}");

    $this->getJson('/fr/search?q=deployer stress')->assertJsonCount(1, 'results');
});

test('the search ignores unpublished content', function () {
    Post::factory()->draft()->create(['title' => ['fr' => 'Brouillon Laravel']]);
    Post::factory()->scheduled()->create(['title' => ['fr' => 'Futur Laravel']]);
    Project::factory()->create(['title' => ['fr' => 'Ancien Laravel'], 'status' => ProjectStatus::Archived]);

    $this->getJson('/fr/search?q=laravel')->assertOk()->assertJsonCount(0, 'results');
});

test('the search leaves out posts while the blog is disabled', function () {
    Post::factory()->create(['title' => ['fr' => 'Article Laravel']]);
    SiteSetting::current()->update(['blog_enabled' => false]);

    $this->getJson('/fr/search?q=laravel')->assertJsonCount(0, 'results');
});

test('a query that is too short returns nothing', function () {
    Post::factory()->create(['title' => ['fr' => 'A']]);

    $this->getJson('/fr/search?q=a')->assertOk()->assertExactJson(['results' => []]);
});

test('a search is not counted as a page visit', function () {
    $this->getJson('/fr/search?q=laravel')->assertOk();

    expect(PageVisit::query()->count())->toBe(0);
});

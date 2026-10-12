<?php

use App\Ai\Agents\ProjectDrafter;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Support\Facades\Http;

function projectDraftJson(array $overrides = []): string
{
    return json_encode([
        'title' => 'Gestion de stock',
        'slug' => 'Gestion de Stock',
        'tagline' => str_repeat('a', 200),
        'role' => 'Développeur full-stack',
        'client' => null,
        'platform' => 'Web',
        'context' => "Une PME suivait son stock sur tableur.\n\nElle voulait un outil partagé.",
        'challenges' => null,
        'realization' => "J'ai conçu une application Laravel.",
        'result' => "L'équipe suit son stock en temps réel.",
        'decisions' => [['choice' => 'Laravel', 'reason' => 'Écosystème complet.'], ['choice' => '', 'reason' => 'Vide']],
        'key_figures' => [],
        'started_on' => '2026-02',
        'ended_on' => 'bientôt',
        'team_size' => 1,
        'technologies' => [],
        'domains' => [],
        'job_profiles' => [999],
        ...$overrides,
    ]);
}

test('guests cannot draft a project', function () {
    $this->postJson(route('admin.ai.draft-project'), ['notes' => 'Un projet'])->assertUnauthorized();
});

test('a project is drafted in french from notes, within the form limits', function () {
    $technology = Technology::factory()->create(['name' => 'Laravel']);
    ProjectDrafter::fake(['```json'."\n".projectDraftJson(['technologies' => [$technology->id, 999]])."\n```"]);
    Http::preventStrayRequests();

    $this->actingAs(User::factory()->create())
        ->postJson(route('admin.ai.draft-project'), ['notes' => 'Outil de stock pour une PME, en Laravel.'])
        ->assertOk()
        ->assertJsonPath('project.title', 'Gestion de stock')
        ->assertJsonPath('project.slug', 'gestion-de-stock')
        ->assertJsonPath('project.tagline', str_repeat('a', 160))
        ->assertJsonPath('project.started_on', '2026-02')
        ->assertJsonPath('project.ended_on', null)
        ->assertJsonPath('project.decisions', [['choice' => 'Laravel', 'reason' => 'Écosystème complet.']])
        ->assertJsonPath('project.technologies', [$technology->id])
        ->assertJsonPath('project.job_profiles', [])
        ->assertJsonMissingPath('project.repo_url');

    ProjectDrafter::assertPrompted(fn ($prompt) => $prompt->contains('Outil de stock pour une PME'));
});

test('a public repository feeds the draft and fills its links', function () {
    Http::fake([
        'api.github.com/repos/armel/stock/languages' => Http::response(['PHP' => 1000, 'TypeScript' => 500]),
        'api.github.com/repos/armel/stock/readme' => Http::response('# Stock, le suivi des inventaires'),
        'api.github.com/repos/armel/stock/contents/composer.json' => Http::response(json_encode(['require' => ['laravel/framework' => '^13.0']])),
        'api.github.com/repos/armel/stock/contents/package.json' => Http::response('', 404),
        'api.github.com/repos/armel/stock/commits*' => Http::response([['commit' => ['message' => "Ajoute l'export CSV\n\nDétails"]]]),
        'api.github.com/repos/armel/stock' => Http::response([
            'full_name' => 'armel/stock',
            'private' => false,
            'html_url' => 'https://github.com/armel/stock',
            'homepage' => 'https://stock.example.com',
            'description' => 'Suivi de stock',
            'topics' => ['laravel'],
        ]),
    ]);
    ProjectDrafter::fake([projectDraftJson()]);

    $this->actingAs(User::factory()->create())
        ->postJson(route('admin.ai.draft-project'), ['github_repository' => 'armel/stock'])
        ->assertOk()
        ->assertJsonPath('project.github_repository', 'armel/stock')
        ->assertJsonPath('project.repo_url', 'https://github.com/armel/stock')
        ->assertJsonPath('project.demo_url', 'https://stock.example.com')
        ->assertJsonPath('project.is_open_source', true);

    ProjectDrafter::assertPrompted(fn ($prompt) => $prompt->contains('Stock, le suivi des inventaires')
        && $prompt->contains('laravel/framework')
        && $prompt->contains("- Ajoute l'export CSV"));
});

test('a private repository is never sent to the AI', function () {
    Http::fake(['api.github.com/repos/armel/client' => Http::response(['full_name' => 'armel/client', 'private' => true])]);
    ProjectDrafter::fake()->preventStrayPrompts();

    $this->actingAs(User::factory()->create())
        ->postJson(route('admin.ai.draft-project'), ['github_repository' => 'armel/client'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('github_repository');

    ProjectDrafter::assertNeverPrompted();
});

test('an unknown repository is rejected', function () {
    Http::fake(['api.github.com/*' => Http::response(['message' => 'Not Found'], 404)]);
    ProjectDrafter::fake()->preventStrayPrompts();

    $this->actingAs(User::factory()->create())
        ->postJson(route('admin.ai.draft-project'), ['github_repository' => 'armel/absent'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('github_repository');
});

test('a source is required', function () {
    $this->actingAs(User::factory()->create())
        ->postJson(route('admin.ai.draft-project'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['github_repository', 'notes']);
});

test('an unreadable answer falls back to the next provider', function () {
    config(['ai.text_assist.providers' => ['groq', 'gemini']]);
    ProjectDrafter::fake(['Voici une proposition de projet…', projectDraftJson()]);

    $this->actingAs(User::factory()->create())
        ->postJson(route('admin.ai.draft-project'), ['notes' => 'Outil de stock'])
        ->assertOk()
        ->assertJsonPath('project.title', 'Gestion de stock');
});

test('the draft is unavailable when every provider fails', function () {
    config(['ai.text_assist.providers' => ['groq']]);
    ProjectDrafter::fake([json_encode(['title' => 'Sans contexte'])]);

    $this->actingAs(User::factory()->create())
        ->postJson(route('admin.ai.draft-project'), ['notes' => 'Outil de stock'])
        ->assertServiceUnavailable();
});

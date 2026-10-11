<?php

use App\Enums\ProjectStatus;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\GitHubActivity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    // Le rendu serveur d'Inertia passe aussi par le client HTTP : coupé ici pour ne
    // compter que les appels à GitHub.
    config(['inertia.ssr.enabled' => false, 'services.github.token' => null]);
    Http::preventStrayRequests();
    Profile::factory()->create(['social_links' => ['github' => 'https://github.com/armel']]);
});

/** @return array<string, mixed> */
function restRepository(string $name, int $stars, string $pushedAt, bool $fork = false, bool $archived = false): array
{
    return [
        'name' => $name,
        'full_name' => "armel/{$name}",
        'description' => "Description de {$name}",
        'language' => 'PHP',
        'stargazers_count' => $stars,
        'html_url' => "https://github.com/armel/{$name}",
        'pushed_at' => $pushedAt,
        'fork' => $fork,
        'archived' => $archived,
        'private' => false,
    ];
}

/** @return array<string, mixed> */
function graphQlRepository(string $nameWithOwner, bool $private = false, int $stars = 0): array
{
    return [
        'nameWithOwner' => $nameWithOwner,
        'name' => str($nameWithOwner)->after('/')->toString(),
        'description' => null,
        'url' => "https://github.com/{$nameWithOwner}",
        'isPrivate' => $private,
        'isArchived' => false,
        'isFork' => false,
        'stargazerCount' => $stars,
        'pushedAt' => '2026-09-01T10:00:00Z',
        'primaryLanguage' => ['name' => 'PHP'],
    ];
}

function fakePublicGitHub(): void
{
    Http::fake([
        'api.github.com/users/armel/repos*' => Http::response([
            restRepository('recent', 0, '2026-10-01T10:00:00Z'),
            restRepository('popular', 12, '2025-01-01T10:00:00Z'),
            restRepository('a-fork', 99, '2026-10-01T10:00:00Z', fork: true),
            restRepository('old-archive', 50, '2024-01-01T10:00:00Z', archived: true),
        ]),
        'api.github.com/users/armel/events/public*' => Http::response([
            ['type' => 'PushEvent', 'created_at' => now()->subDays(2)->toIso8601String()],
            ['type' => 'PushEvent', 'created_at' => now()->subDays(40)->toIso8601String()],
        ]),
        'api.github.com/users/armel' => Http::response(['login' => 'armel', 'html_url' => 'https://github.com/armel', 'public_repos' => 24, 'followers' => 7]),
    ]);
}

function fakeGraphQlGitHub(): void
{
    Http::fake(['api.github.com/graphql' => Http::response(['data' => ['viewer' => [
        'login' => 'armel',
        'url' => 'https://github.com/armel',
        'followers' => ['totalCount' => 7],
        'publicRepositories' => ['totalCount' => 24],
        'repositories' => ['nodes' => [graphQlRepository('armel/public-repo', stars: 3), graphQlRepository('armel/secret', private: true, stars: 50)]],
        'repositoriesContributedTo' => ['nodes' => [graphQlRepository('laravel/framework', stars: 30000)]],
        'contributionsCollection' => [
            'totalCommitContributions' => 40,
            'totalPullRequestContributions' => 3,
            'totalIssueContributions' => 1,
            'totalPullRequestReviewContributions' => 2,
            'restrictedContributionsCount' => 10,
        ],
        'calendar' => [
            'contributionCalendar' => ['weeks' => [['contributionDays' => [
                ['date' => '2026-10-05', 'contributionCount' => 0, 'contributionLevel' => 'NONE'],
                ['date' => '2026-10-06', 'contributionCount' => 6, 'contributionLevel' => 'FOURTH_QUARTILE'],
            ]]]],
            'commitContributionsByRepository' => [
                ['repository' => ['nameWithOwner' => 'armel/public-repo', 'isPrivate' => false], 'contributions' => ['nodes' => [['occurredAt' => '2026-10-06T07:00:00Z', 'commitCount' => 3]]]],
                ['repository' => ['nameWithOwner' => 'armel/secret', 'isPrivate' => true], 'contributions' => ['nodes' => [['occurredAt' => '2026-10-06T07:00:00Z', 'commitCount' => 1]]]],
            ],
            'pullRequestContributionsByRepository' => [
                ['repository' => ['nameWithOwner' => 'armel/public-repo', 'isPrivate' => false], 'contributions' => ['nodes' => [['occurredAt' => '2026-10-06T09:00:00Z']]]],
            ],
            'pullRequestReviewContributionsByRepository' => [],
            'issueContributionsByRepository' => [],
        ],
    ]]])]);
}

test('with a token, the calendar lists each day with the repositories worked on, private ones kept anonymous', function () {
    config(['services.github.token' => 'github_pat_test', 'app.timezone' => 'UTC']);
    fakeGraphQlGitHub();

    $this->artisan('github:sync')->assertSuccessful();

    $this->get('/fr/about')->assertInertia(fn ($page) => $page
        ->has('github.calendar', 2)
        ->where('github.calendar.0', ['date' => '2026-10-05', 'count' => 0, 'level' => 0, 'repositories' => []])
        ->where('github.calendar.1.level', 4)
        ->where('github.calendar.1.repositories', [['name' => 'armel/public-repo', 'count' => 4, 'project' => null], ['name' => null, 'count' => 1, 'project' => null]]));
});

test('without a token, there is no calendar', function () {
    fakePublicGitHub();

    $this->artisan('github:sync')->assertSuccessful();

    expect(app(GitHubActivity::class)->forDisplay()['calendar'])->toBeNull();
});

test('without a token, the sync reads my public repositories and the automatic choice skips forks and archives', function () {
    fakePublicGitHub();

    $this->artisan('github:sync')->assertSuccessful();

    $display = app(GitHubActivity::class)->forDisplay();
    expect($display['public_repos'])->toBe(24)
        ->and($display['contributions_last_30_days'])->toBe(1)
        ->and(array_column($display['repositories'], 'name'))->toBe(['popular', 'recent']);
});

test('with a token, the sync adds my private repositories and contributions, never shown automatically', function () {
    config(['services.github.token' => 'github_pat_test']);
    fakeGraphQlGitHub();

    $this->artisan('github:sync')->assertSuccessful();

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer github_pat_test'));
    $github = app(GitHubActivity::class);
    expect(array_column($github->cached()['available'], 'full_name'))->toBe(['armel/public-repo', 'armel/secret', 'laravel/framework'])
        ->and($github->forDisplay()['contributions_last_30_days'])->toBe(56)
        ->and(array_column($github->forDisplay()['repositories'], 'full_name'))->toBe(['armel/public-repo']);
});

test('chosen repositories are shown in order, a private one without its link', function () {
    config(['services.github.token' => 'github_pat_test']);
    fakeGraphQlGitHub();
    $this->artisan('github:sync');

    $this->actingAs(User::factory()->create())
        ->put(route('admin.github.update'), ['repositories' => ['laravel/framework', 'armel/secret']])
        ->assertSessionHasNoErrors();

    $this->get('/fr/about')->assertInertia(fn ($page) => $page
        ->has('github.repositories', 2)
        ->where('github.repositories.0.full_name', 'laravel/framework')
        ->where('github.repositories.0.contribution', true)
        ->where('github.repositories.1.private', true)
        ->where('github.repositories.1.url', null));
});

test('only repositories from the last sync can be chosen, and an empty choice goes back to automatic', function () {
    fakePublicGitHub();
    $this->artisan('github:sync');
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('admin.github.update'), ['repositories' => ['someone/unknown']])
        ->assertSessionHasErrors('repositories.0');

    $this->actingAs($user)->put(route('admin.github.update'), ['repositories' => []])->assertSessionHasNoErrors();
    expect(SiteSetting::current()->github_repositories)->toBe([]);
});

test('a GitHub failure keeps the last known activity', function () {
    Cache::forever(GitHubActivity::CACHE_KEY, ['username' => 'armel', 'available' => []]);
    Http::fake(['api.github.com/*' => Http::response([], 500)]);

    $this->artisan('github:sync')->assertFailed();

    expect(app(GitHubActivity::class)->cached()['username'])->toBe('armel');
});

test('without a token nor a GitHub link, nothing is requested and the about page has no GitHub section', function () {
    Profile::query()->update(['social_links' => ['linkedin' => 'https://linkedin.com/in/armel']]);

    $this->artisan('github:sync')->assertSuccessful();

    Http::assertNothingSent();
    $this->get('/fr/about')->assertInertia(fn ($page) => $page->where('github', null));
});

test('the selection is available through the API', function () {
    fakePublicGitHub();
    $this->artisan('github:sync');
    Sanctum::actingAs(User::factory()->create());

    $this->putJson(route('api.v1.github.update'), ['repositories' => ['armel/recent']])
        ->assertOk()
        ->assertJsonPath('selected', ['armel/recent'])
        ->assertJsonPath('has_token', false)
        ->assertJsonCount(3, 'available');
});

test('a repository linked to a published project points to it, and a private one takes its title', function () {
    config(['services.github.token' => 'github_pat_test', 'app.timezone' => 'UTC']);
    fakeGraphQlGitHub();
    $this->artisan('github:sync');
    SiteSetting::current()->update(['github_repositories' => ['armel/public-repo', 'armel/secret']]);
    $secret = Project::factory()->create(['title' => ['fr' => 'Coffre', 'en' => 'Vault'], 'slug' => 'coffre', 'github_repository' => 'armel/secret']);
    Project::factory()->create(['status' => ProjectStatus::Archived, 'github_repository' => 'armel/public-repo']);
    $project = ['title' => 'Coffre', 'url' => route('projects.show', ['locale' => 'fr', 'project' => 'coffre'])];

    $this->get('/fr/about')->assertInertia(fn ($page) => $page
        ->where('github.repositories.0.project', null)
        ->where('github.repositories.1.project', $project)
        ->where('github.calendar.1.repositories', [
            ['name' => 'armel/public-repo', 'count' => 4, 'project' => null],
            ['name' => 'Coffre', 'count' => 1, 'project' => $project],
        ]));

    $this->get("/fr/projects/{$secret->slug}")->assertInertia(fn ($page) => $page
        ->where('github.name', null)
        ->where('github.url', null)
        ->where('github.private', true)
        ->where('github.total', 1));
});

test('a project page without a linked repository has no GitHub panel', function () {
    $project = Project::factory()->create(['github_repository' => null]);

    $this->get("/fr/projects/{$project->slug}")->assertInertia(fn ($page) => $page->where('github', null));
});

test('the linked repository must look like owner/name', function () {
    $project = Project::factory()->create();
    $attributes = [
        'title' => $project->getTranslations('title'),
        'slug' => $project->slug,
        'context' => $project->getTranslations('context'),
        'realization' => $project->getTranslations('realization'),
        'result' => $project->getTranslations('result'),
        'status' => 'published',
        'sort_order' => 0,
    ];
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('admin.projects.update', $project), [...$attributes, 'github_repository' => 'https://github.com/armel/secret'])
        ->assertSessionHasErrors('github_repository');

    $this->actingAs($user)->put(route('admin.projects.update', $project), [...$attributes, 'github_repository' => 'armel/secret'])
        ->assertSessionHasNoErrors();
    expect($project->fresh()->github_repository)->toBe('armel/secret');
});

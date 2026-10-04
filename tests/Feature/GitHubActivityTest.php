<?php

use App\Models\Profile;
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
    ]]])]);
}

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

<?php

use App\Models\Profile;
use App\Services\GitHubActivity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Le rendu serveur d'Inertia passe aussi par le client HTTP : coupé ici pour ne
    // compter que les appels à GitHub.
    config(['inertia.ssr.enabled' => false]);
    Http::preventStrayRequests();
});

/** @return array<string, mixed> */
function githubRepository(string $name, int $stars, string $pushedAt, bool $fork = false, bool $archived = false): array
{
    return [
        'name' => $name,
        'description' => "Description de {$name}",
        'language' => 'PHP',
        'stargazers_count' => $stars,
        'html_url' => "https://github.com/armel/{$name}",
        'pushed_at' => $pushedAt,
        'fork' => $fork,
        'archived' => $archived,
    ];
}

function fakeGitHub(): void
{
    Http::fake([
        'api.github.com/users/armel/repos*' => Http::response([
            githubRepository('recent', 0, '2026-10-01T10:00:00Z'),
            githubRepository('popular', 12, '2025-01-01T10:00:00Z'),
            githubRepository('a-fork', 99, '2026-10-01T10:00:00Z', fork: true),
            githubRepository('old-archive', 50, '2024-01-01T10:00:00Z', archived: true),
        ]),
        'api.github.com/users/armel/events/public*' => Http::response([
            ['type' => 'PushEvent', 'created_at' => now()->subDays(2)->toIso8601String()],
            ['type' => 'PushEvent', 'created_at' => now()->subDays(40)->toIso8601String()],
            ['type' => 'WatchEvent', 'created_at' => now()->subDay()->toIso8601String()],
        ]),
        'api.github.com/users/armel' => Http::response([
            'login' => 'armel',
            'html_url' => 'https://github.com/armel',
            'public_repos' => 24,
            'followers' => 7,
        ]),
    ]);
}

test('the sync keeps my own repositories, most starred first, and counts recent pushes', function () {
    Profile::factory()->create(['social_links' => ['github' => 'https://github.com/armel']]);
    fakeGitHub();

    $this->artisan('github:sync')->assertSuccessful();

    $activity = app(GitHubActivity::class)->cached();
    expect($activity['public_repos'])->toBe(24)
        ->and($activity['followers'])->toBe(7)
        ->and($activity['pushes_last_30_days'])->toBe(1)
        ->and(array_column($activity['repositories'], 'name'))->toBe(['popular', 'recent']);
});

test('the about page shows the cached GitHub activity', function () {
    Profile::factory()->create(['social_links' => ['github' => 'https://github.com/armel']]);
    fakeGitHub();
    $this->artisan('github:sync');

    $this->get('/fr/about')->assertInertia(fn ($page) => $page
        ->where('github.username', 'armel')
        ->has('github.repositories', 2));
});

test('a GitHub failure keeps the last known activity', function () {
    Profile::factory()->create(['social_links' => ['github' => 'https://github.com/armel']]);
    Cache::forever(GitHubActivity::CACHE_KEY, ['username' => 'armel', 'repositories' => []]);
    Http::fake(['api.github.com/*' => Http::response([], 500)]);

    $this->artisan('github:sync')->assertFailed();

    expect(app(GitHubActivity::class)->cached()['username'])->toBe('armel');
});

test('without a GitHub link, nothing is requested and the about page has no GitHub section', function () {
    Profile::factory()->create(['social_links' => ['linkedin' => 'https://linkedin.com/in/armel']]);

    $this->artisan('github:sync')->assertSuccessful();

    Http::assertNothingSent();
    $this->get('/fr/about')->assertInertia(fn ($page) => $page->where('github', null));
});

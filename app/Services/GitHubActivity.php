<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\SiteSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Mon activité GitHub (dépôts, compteurs), pour la page À propos.
 *
 * La commande `github:sync` interroge GitHub et met en cache tous les dépôts candidats ;
 * la page ne lit que ce cache, pour ne jamais attendre GitHub ni tomber avec lui. Un échec
 * garde la dernière version connue.
 *
 * Avec un jeton (`services.github.token`), l'API GraphQL ajoute les dépôts auxquels j'ai
 * contribué et mes dépôts privés. Un dépôt privé n'est jamais montré automatiquement :
 * seulement s'il est choisi dans l'administration, et alors sans lien.
 */
class GitHubActivity
{
    public const string CACHE_KEY = 'github:activity';

    private const string API_URL = 'https://api.github.com';

    /** Dépôts montrés quand aucun n'est choisi dans l'administration. */
    private const int AUTOMATIC_COUNT = 6;

    /** Dépôts montrés au plus quand ils sont choisis. */
    public const int MAX_SELECTED = 12;

    private const string GRAPHQL_QUERY = <<<'GRAPHQL'
        query($from: DateTime!) {
          viewer {
            login
            url
            followers { totalCount }
            publicRepositories: repositories(privacy: PUBLIC, ownerAffiliations: OWNER) { totalCount }
            repositories(first: 100, ownerAffiliations: OWNER, orderBy: {field: PUSHED_AT, direction: DESC}) { nodes { ...repository } }
            repositoriesContributedTo(first: 100, includeUserRepositories: false, contributionTypes: [COMMIT, PULL_REQUEST, REPOSITORY], orderBy: {field: PUSHED_AT, direction: DESC}) { nodes { ...repository } }
            contributionsCollection(from: $from) { totalCommitContributions totalPullRequestContributions totalIssueContributions totalPullRequestReviewContributions restrictedContributionsCount }
          }
        }
        fragment repository on Repository {
          nameWithOwner name description url isPrivate isArchived isFork stargazerCount pushedAt
          primaryLanguage { name }
        }
        GRAPHQL;

    /**
     * L'activité affichée sur le site : les dépôts choisis dans l'ordre, sinon le choix
     * automatique. Null tant qu'aucune synchronisation n'a réussi.
     *
     * @return array{username: string, profile_url: string, public_repos: int, followers: int, contributions_last_30_days: int, repositories: list<array<string, mixed>>, synced_at: string}|null
     */
    public function forDisplay(): ?array
    {
        $cached = $this->cached();

        if ($cached === null) {
            return null;
        }

        $available = collect($cached['available'] ?? [])->keyBy('full_name');
        $selected = SiteSetting::current()->github_repositories ?? [];

        $repositories = $selected !== []
            ? collect($selected)->map(fn (string $name) => $available->get($name))->filter()->take(self::MAX_SELECTED)
            : $available
                ->filter(fn (array $repository): bool => ! $repository['private'] && ! $repository['contribution'] && ! $repository['archived'])
                ->sortBy([
                    fn (array $a, array $b): int => $b['stars'] <=> $a['stars'],
                    fn (array $a, array $b): int => strcmp((string) $b['pushed_at'], (string) $a['pushed_at']),
                ])
                ->take(self::AUTOMATIC_COUNT);

        return [
            'username' => $cached['username'],
            'profile_url' => $cached['profile_url'],
            'public_repos' => $cached['public_repos'],
            'followers' => $cached['followers'],
            'contributions_last_30_days' => $cached['contributions_last_30_days'],
            // Un dépôt privé n'a pas de lien : les visiteurs tomberaient sur une 404.
            'repositories' => $repositories
                ->map(fn (array $repository): array => [
                    ...collect($repository)->only(['full_name', 'name', 'description', 'language', 'stars', 'pushed_at', 'private', 'contribution'])->all(),
                    'url' => $repository['private'] ? null : $repository['url'],
                ])
                ->values()
                ->all(),
            'synced_at' => $cached['synced_at'],
        ];
    }

    /** @return array<string, mixed>|null */
    public function cached(): ?array
    {
        return Cache::get(self::CACHE_KEY);
    }

    /**
     * Interroge GitHub et remplace le cache. Renvoie false si rien n'a pu être lu
     * (pas de lien GitHub ni de jeton, API injoignable) : le cache reste intact.
     */
    public function sync(): bool
    {
        try {
            $activity = $this->hasToken() ? $this->fetchWithToken() : $this->fetchPublic();
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }

        if ($activity === null) {
            return false;
        }

        Cache::forever(self::CACHE_KEY, [...$activity, 'synced_at' => now()->toIso8601String()]);

        return true;
    }

    public function hasToken(): bool
    {
        return filled(config('services.github.token'));
    }

    /** L'identifiant tiré du lien GitHub du profil (https://github.com/<identifiant>). */
    public function username(): ?string
    {
        $url = Profile::query()->first()?->social_links['github'] ?? null;

        if (! is_string($url) || ! preg_match('~github\.com/([A-Za-z0-9-]+)~i', $url, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Avec un jeton : mes dépôts (privés compris), ceux auxquels j'ai contribué, et mes
     * contributions des 30 derniers jours (privées comprises, en nombre seulement).
     *
     * @return array<string, mixed>
     */
    private function fetchWithToken(): array
    {
        $viewer = $this->client()
            ->post('/graphql', ['query' => self::GRAPHQL_QUERY, 'variables' => ['from' => now()->subDays(30)->toIso8601String()]])
            ->throw()
            ->json('data.viewer');

        $contributions = $viewer['contributionsCollection'];

        return [
            'username' => $viewer['login'],
            'profile_url' => $viewer['url'],
            'public_repos' => (int) $viewer['publicRepositories']['totalCount'],
            'followers' => (int) $viewer['followers']['totalCount'],
            'contributions_last_30_days' => (int) ($contributions['totalCommitContributions']
                + $contributions['totalPullRequestContributions']
                + $contributions['totalIssueContributions']
                + $contributions['totalPullRequestReviewContributions']
                + $contributions['restrictedContributionsCount']),
            'available' => collect($viewer['repositories']['nodes'])
                ->reject(fn (array $node): bool => $node['isFork'])
                ->map(fn (array $node): array => $this->fromGraphQl($node, contribution: false))
                ->concat(collect($viewer['repositoriesContributedTo']['nodes'])->map(fn (array $node): array => $this->fromGraphQl($node, contribution: true)))
                ->unique('full_name')
                ->values()
                ->all(),
        ];
    }

    /**
     * Sans jeton : mes dépôts publics et mes pushs publics des 30 derniers jours
     * (l'API publique ne connaît ni les contributions ni les dépôts privés).
     *
     * @return array<string, mixed>|null
     */
    private function fetchPublic(): ?array
    {
        $username = $this->username();

        if ($username === null) {
            return null;
        }

        $user = $this->client()->get("/users/{$username}")->throw()->json();
        $repositories = $this->client()->get("/users/{$username}/repos", ['sort' => 'pushed', 'per_page' => 100])->throw()->json();
        $events = $this->client()->get("/users/{$username}/events/public", ['per_page' => 100])->throw()->json();
        $since = now()->subDays(30);

        return [
            'username' => $user['login'],
            'profile_url' => $user['html_url'],
            'public_repos' => (int) $user['public_repos'],
            'followers' => (int) $user['followers'],
            'contributions_last_30_days' => collect($events)
                ->filter(fn (array $event): bool => $event['type'] === 'PushEvent' && Carbon::parse($event['created_at'])->gte($since))
                ->count(),
            'available' => collect($repositories)
                ->reject(fn (array $repository): bool => $repository['fork'])
                ->map(fn (array $repository): array => [
                    'full_name' => $repository['full_name'],
                    'name' => $repository['name'],
                    'description' => $repository['description'],
                    'language' => $repository['language'],
                    'stars' => (int) $repository['stargazers_count'],
                    'url' => $repository['html_url'],
                    'pushed_at' => $repository['pushed_at'],
                    'private' => (bool) ($repository['private'] ?? false),
                    'archived' => (bool) $repository['archived'],
                    'contribution' => false,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function fromGraphQl(array $node, bool $contribution): array
    {
        return [
            'full_name' => $node['nameWithOwner'],
            // Un dépôt d'un autre propriétaire garde son nom complet (« organisation/dépôt »).
            'name' => $contribution ? $node['nameWithOwner'] : $node['name'],
            'description' => $node['description'],
            'language' => $node['primaryLanguage']['name'] ?? null,
            'stars' => (int) $node['stargazerCount'],
            'url' => $node['url'],
            'pushed_at' => $node['pushedAt'],
            'private' => (bool) $node['isPrivate'],
            'archived' => (bool) $node['isArchived'],
            'contribution' => $contribution,
        ];
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(self::API_URL)
            ->timeout(15)
            ->acceptJson()
            ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28'])
            ->when(config('services.github.token'), fn (PendingRequest $request, string $token) => $request->withToken($token));
    }
}

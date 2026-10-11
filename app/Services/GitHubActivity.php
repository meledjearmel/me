<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
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

    /** Niveaux d'intensité du calendrier GitHub, de 0 (rien) à 4 (le quart le plus actif). */
    private const array CONTRIBUTION_LEVELS = ['NONE' => 0, 'FIRST_QUARTILE' => 1, 'SECOND_QUARTILE' => 2, 'THIRD_QUARTILE' => 3, 'FOURTH_QUARTILE' => 4];

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
            calendar: contributionsCollection {
              contributionCalendar { weeks { contributionDays { date contributionCount contributionLevel } } }
              commitContributionsByRepository(maxRepositories: 100) { repository { nameWithOwner isPrivate } contributions(first: 100, orderBy: {field: OCCURRED_AT, direction: DESC}) { nodes { occurredAt commitCount } } }
              pullRequestContributionsByRepository(maxRepositories: 100) { repository { nameWithOwner isPrivate } contributions(first: 100, orderBy: {direction: DESC}) { nodes { occurredAt } } }
              pullRequestReviewContributionsByRepository(maxRepositories: 100) { repository { nameWithOwner isPrivate } contributions(first: 100, orderBy: {direction: DESC}) { nodes { occurredAt } } }
              issueContributionsByRepository(maxRepositories: 100) { repository { nameWithOwner isPrivate } contributions(first: 100, orderBy: {direction: DESC}) { nodes { occurredAt } } }
            }
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
     * Un dépôt lié à un projet publié porte ce projet (`project`) : la carte et l'infobulle
     * du calendrier y renvoient, et un dépôt privé prend alors le titre du projet.
     *
     * @return array{username: string, profile_url: string, public_repos: int, followers: int, contributions_last_30_days: int, repositories: list<array<string, mixed>>, calendar: list<array{date: string, count: int, level: int, repositories: list<array{name: string|null, count: int, project: array{title: string, url: string}|null}>}>|null, synced_at: string}|null
     */
    public function forDisplay(): ?array
    {
        $cached = $this->cached();

        if ($cached === null) {
            return null;
        }

        $projects = $this->publishedProjects();

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
                    'project' => $projects->get(strtolower($repository['full_name'])),
                ])
                ->values()
                ->all(),
            // Un dépôt privé reste anonyme, sauf s'il est lié à un projet publié : on montre
            // alors le titre du projet, jamais le nom du dépôt.
            'calendar' => isset($cached['calendar'])
                ? collect($cached['calendar'])
                    ->map(fn (array $day): array => [
                        ...$day,
                        'repositories' => collect($day['repositories'])
                            ->map(function (array $repository) use ($projects): array {
                                $project = $projects->get(strtolower($repository['full_name']));

                                return [
                                    'name' => $project['title'] ?? ($repository['private'] ? null : $repository['full_name']),
                                    'count' => $repository['count'],
                                    'project' => $project,
                                ];
                            })
                            ->all(),
                    ])
                    ->all()
                : null,
            'synced_at' => $cached['synced_at'],
        ];
    }

    /**
     * L'encart GitHub de la page d'un projet : son dépôt lié et ses contributions semaine
     * par semaine sur l'année. Un dépôt privé n'a ni nom ni lien. Null sans dépôt lié ou
     * s'il est absent de la dernière synchronisation.
     *
     * @return array{name: string|null, url: string|null, language: string|null, stars: int, pushed_at: string|null, private: bool, total: int, weeks: list<int>|null}|null
     */
    public function forProject(Project $project): ?array
    {
        if (blank($project->github_repository) || ($cached = $this->cached()) === null) {
            return null;
        }

        $fullName = strtolower($project->github_repository);
        $repository = collect($cached['available'] ?? [])->first(fn (array $repository): bool => strtolower($repository['full_name']) === $fullName);

        if ($repository === null) {
            return null;
        }

        $weeks = isset($cached['calendar'])
            ? collect($cached['calendar'])
                ->chunk(7)
                ->map(fn ($days): int => $days->sum(fn (array $day): int => collect($day['repositories'])
                    ->filter(fn (array $entry): bool => strtolower($entry['full_name']) === $fullName)
                    ->sum('count')))
                ->values()
                ->all()
            : null;

        return [
            'name' => $repository['private'] ? null : $repository['full_name'],
            'url' => $repository['private'] ? null : $repository['url'],
            'language' => $repository['language'],
            'stars' => $repository['stars'],
            'pushed_at' => $repository['pushed_at'],
            'private' => $repository['private'],
            'total' => $weeks === null ? 0 : array_sum($weeks),
            'weeks' => $weeks,
        ];
    }

    /**
     * Les projets publiés liés à un dépôt, par nom de dépôt en minuscules.
     *
     * @return Collection<string, array{title: string, url: string}>
     */
    private function publishedProjects(): Collection
    {
        return Project::query()
            ->where('status', ProjectStatus::Published)
            ->whereNotNull('github_repository')
            ->get(['id', 'title', 'slug', 'github_repository'])
            ->mapWithKeys(fn (Project $project): array => [strtolower($project->github_repository) => [
                'title' => $project->title,
                'url' => route('projects.show', ['locale' => app()->getLocale(), 'project' => $project->slug]),
            ]]);
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
     * contributions des 30 derniers jours (privées comprises, en nombre seulement) et le
     * calendrier de l'année, jour par jour avec les dépôts travaillés.
     *
     * @return array<string, mixed>
     */
    private function fetchWithToken(): array
    {
        $response = $this->client()
            ->post('/graphql', ['query' => self::GRAPHQL_QUERY, 'variables' => ['from' => now()->subDays(30)->toIso8601String()]])
            ->throw();

        // GraphQL répond 200 même quand la requête est refusée : l'erreur est dans le corps.
        if ($response->json('errors')) {
            throw new RuntimeException('GitHub GraphQL : '.$response->json('errors.0.message'));
        }

        $viewer = $response->json('data.viewer');

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
            'calendar' => $this->calendar($viewer['calendar']),
        ];
    }

    /**
     * Les jours de l'année (du plus ancien au plus récent) avec, pour chacun, les dépôts
     * travaillés et leur nombre de contributions (commits, pull requests, revues, issues).
     *
     * @param  array<string, mixed>  $collection
     * @return list<array{date: string, count: int, level: int, repositories: list<array{full_name: string, private: bool, count: int}>}>
     */
    private function calendar(array $collection): array
    {
        $byDay = [];

        foreach (['commit', 'pullRequest', 'pullRequestReview', 'issue'] as $type) {
            foreach ($collection["{$type}ContributionsByRepository"] ?? [] as $entry) {
                $name = $entry['repository']['nameWithOwner'];

                foreach ($entry['contributions']['nodes'] as $node) {
                    $date = Carbon::parse($node['occurredAt'])->timezone(config('app.timezone'))->toDateString();
                    $byDay[$date][$name] ??= ['full_name' => $name, 'private' => (bool) $entry['repository']['isPrivate'], 'count' => 0];
                    $byDay[$date][$name]['count'] += (int) ($node['commitCount'] ?? 1);
                }
            }
        }

        return collect($collection['contributionCalendar']['weeks'])
            ->flatMap(fn (array $week): array => $week['contributionDays'])
            ->map(fn (array $day): array => [
                'date' => $day['date'],
                'count' => (int) $day['contributionCount'],
                'level' => self::CONTRIBUTION_LEVELS[$day['contributionLevel']] ?? 0,
                'repositories' => collect($byDay[$day['date']] ?? [])->sortByDesc('count')->values()->all(),
            ])
            ->values()
            ->all();
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
            'calendar' => null,
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

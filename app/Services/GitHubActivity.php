<?php

namespace App\Services;

use App\Models\Profile;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Mon activité publique sur GitHub (dépôts, compteurs), pour la page À propos.
 *
 * La commande `github:sync` interroge l'API et met le résultat en cache ; la page ne lit
 * que le cache, pour ne jamais attendre GitHub ni tomber avec lui. Un échec garde la
 * dernière version connue.
 */
class GitHubActivity
{
    public const string CACHE_KEY = 'github:activity';

    private const string API_URL = 'https://api.github.com';

    /** Dépôts montrés sur la page. */
    private const int REPOSITORY_COUNT = 6;

    /**
     * @return array{username: string, profile_url: string, public_repos: int, followers: int, pushes_last_30_days: int, repositories: list<array{name: string, description: string|null, language: string|null, stars: int, url: string, pushed_at: string}>, synced_at: string}|null
     */
    public function cached(): ?array
    {
        return Cache::get(self::CACHE_KEY);
    }

    /**
     * Interroge GitHub et remplace le cache. Renvoie false si rien n'a pu être lu
     * (pas de lien GitHub sur le profil, API injoignable) : le cache reste intact.
     */
    public function sync(): bool
    {
        $username = $this->username();

        if ($username === null) {
            return false;
        }

        try {
            $user = $this->client()->get("/users/{$username}")->throw()->json();
            $repositories = $this->client()->get("/users/{$username}/repos", ['sort' => 'pushed', 'per_page' => 100])->throw()->json();
            $events = $this->client()->get("/users/{$username}/events/public", ['per_page' => 100])->throw()->json();
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }

        Cache::forever(self::CACHE_KEY, [
            'username' => $user['login'],
            'profile_url' => $user['html_url'],
            'public_repos' => (int) $user['public_repos'],
            'followers' => (int) $user['followers'],
            'pushes_last_30_days' => $this->recentPushes($events),
            'repositories' => $this->highlightedRepositories($repositories),
            'synced_at' => now()->toIso8601String(),
        ]);

        return true;
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

    private function client(): PendingRequest
    {
        return Http::baseUrl(self::API_URL)
            ->timeout(10)
            ->acceptJson()
            ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28'])
            ->when(config('services.github.token'), fn (PendingRequest $request, string $token) => $request->withToken($token));
    }

    /**
     * Mes propres dépôts (ni forks ni archivés) : les plus étoilés, puis les plus récents.
     *
     * @param  list<array<string, mixed>>  $repositories
     * @return list<array{name: string, description: string|null, language: string|null, stars: int, url: string, pushed_at: string}>
     */
    private function highlightedRepositories(array $repositories): array
    {
        return collect($repositories)
            ->reject(fn (array $repository): bool => $repository['fork'] || $repository['archived'])
            ->sortBy([
                fn (array $a, array $b): int => $b['stargazers_count'] <=> $a['stargazers_count'],
                fn (array $a, array $b): int => strcmp($b['pushed_at'], $a['pushed_at']),
            ])
            ->take(self::REPOSITORY_COUNT)
            ->map(fn (array $repository): array => [
                'name' => $repository['name'],
                'description' => $repository['description'],
                'language' => $repository['language'],
                'stars' => (int) $repository['stargazers_count'],
                'url' => $repository['html_url'],
                'pushed_at' => $repository['pushed_at'],
            ])
            ->values()
            ->all();
    }

    /**
     * Pushs publics des 30 derniers jours (l'API n'expose que les 90 derniers jours d'événements).
     *
     * @param  list<array<string, mixed>>  $events
     */
    private function recentPushes(array $events): int
    {
        $since = now()->subDays(30);

        return collect($events)
            ->filter(fn (array $event): bool => $event['type'] === 'PushEvent' && Carbon::parse($event['created_at'])->gte($since))
            ->count();
    }
}

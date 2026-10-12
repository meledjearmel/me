<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Lit ce qu'un dépôt GitHub dit de lui-même (description, langages, README, dépendances,
 * derniers commits) pour servir de source à la rédaction d'un projet par l'IA.
 */
class GitHubRepositoryReader
{
    private const string API_URL = 'https://api.github.com';

    /** Le README est tronqué au-delà : l'essentiel d'un projet tient dans son début. */
    private const int README_MAX_LENGTH = 6000;

    private const int COMMITS_COUNT = 20;

    /**
     * Null si le dépôt est introuvable ou si GitHub ne répond pas.
     *
     * @return array{full_name: string, private: bool, url: string, homepage: string|null, source: string}|null
     */
    public function read(string $fullName): ?array
    {
        try {
            $repository = $this->client()->get("/repos/{$fullName}");
        } catch (Throwable) {
            return null;
        }

        if (! $repository->successful()) {
            return null;
        }

        $homepage = $repository->json('homepage');

        return [
            'full_name' => $repository->json('full_name'),
            'private' => (bool) $repository->json('private'),
            'url' => $repository->json('html_url'),
            'homepage' => filled($homepage) ? $homepage : null,
            'source' => $this->describe($fullName, $repository),
        ];
    }

    private function describe(string $fullName, Response $repository): string
    {
        $sections = [
            "Dépôt : {$repository->json('full_name')}",
            'Description : '.($repository->json('description') ?: '(aucune)'),
            'Sujets : '.(implode(', ', $repository->json('topics') ?? []) ?: '(aucun)'),
            'Créé le : '.Str::substr((string) $repository->json('created_at'), 0, 10),
            'Dernier envoi : '.Str::substr((string) $repository->json('pushed_at'), 0, 10),
        ];

        $languages = $this->json("/repos/{$fullName}/languages");

        if (filled($languages)) {
            $sections[] = 'Langages : '.implode(', ', array_keys($languages));
        }

        foreach (['composer.json' => ['require', 'require-dev'], 'package.json' => ['dependencies', 'devDependencies']] as $file => $keys) {
            $manifest = json_decode((string) $this->raw("/repos/{$fullName}/contents/{$file}"), true);

            if (is_array($manifest)) {
                $packages = collect($keys)->flatMap(fn (string $key): array => array_keys($manifest[$key] ?? []))->implode(', ');
                $sections[] = "Dépendances ({$file}) : {$packages}";
            }
        }

        $commits = $this->json("/repos/{$fullName}/commits", ['per_page' => self::COMMITS_COUNT]);

        if (filled($commits)) {
            $messages = collect($commits)
                ->map(fn (array $commit): string => '- '.Str::of($commit['commit']['message'] ?? '')->before("\n")->trim())
                ->implode("\n");
            $sections[] = "Derniers commits :\n{$messages}";
        }

        $readme = $this->raw("/repos/{$fullName}/readme");

        if (filled($readme)) {
            $sections[] = "README :\n".Str::limit($readme, self::README_MAX_LENGTH, '…');
        }

        return implode("\n\n", $sections);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<mixed>|null
     */
    private function json(string $path, array $query = []): ?array
    {
        try {
            $response = $this->client()->get($path, $query);
        } catch (Throwable) {
            return null;
        }

        return $response->successful() ? $response->json() : null;
    }

    /** Contenu brut d'un fichier du dépôt, null s'il n'existe pas. */
    private function raw(string $path): ?string
    {
        try {
            $response = $this->client()->accept('application/vnd.github.raw+json')->get($path);
        } catch (Throwable) {
            return null;
        }

        return $response->successful() ? $response->body() : null;
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(self::API_URL)
            ->timeout(10)
            ->acceptJson()
            ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28'])
            ->when(config('services.github.token'), fn (PendingRequest $request, string $token) => $request->withToken($token));
    }
}

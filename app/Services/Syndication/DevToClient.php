<?php

namespace App\Services\Syndication;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Publie ou met à jour un article sur dev.to (API Forem, clé d'API du compte), avec son lien
 * canonique vers le site : dev.to l'indique aux moteurs de recherche, l'original reste le site.
 */
class DevToClient
{
    private const string ENDPOINT = 'https://dev.to/api/articles';

    /**
     * @param  array{title: string, markdown: string, description: string|null, canonical_url: string, tags: list<string>, cover_url: string|null}  $article
     * @return array{id: string, url: string}
     *
     * @throws RuntimeException L'API a refusé l'article (message de dev.to).
     */
    public function publish(string $apiKey, array $article, ?string $externalId = null): array
    {
        $payload = ['article' => array_filter([
            'title' => $article['title'],
            'body_markdown' => $article['markdown'],
            'published' => true,
            'canonical_url' => $article['canonical_url'],
            'description' => $article['description'],
            // dev.to : 4 tags au plus, en minuscules, lettres et chiffres seulement.
            'tags' => collect($article['tags'])
                ->map(fn (string $tag): string => (string) preg_replace('/[^a-z0-9]/', '', strtolower(Str::ascii($tag))))
                ->filter()
                ->unique()
                ->take(4)
                ->values()
                ->all(),
            'main_image' => $article['cover_url'],
        ], fn (mixed $value): bool => $value !== null && $value !== [])];

        $request = Http::withHeaders(['api-key' => $apiKey, 'Accept' => 'application/vnd.forem.api-v1+json'])->timeout(20);
        $response = $externalId === null
            ? $request->post(self::ENDPOINT, $payload)
            : $request->put(self::ENDPOINT.'/'.$externalId, $payload);

        if ($response->failed()) {
            throw new RuntimeException('dev.to : '.($response->json('error') ?? $response->json('message') ?? "erreur {$response->status()}"));
        }

        return ['id' => (string) $response->json('id'), 'url' => (string) $response->json('url')];
    }
}

<?php

namespace App\Services\Syndication;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Publie ou met à jour un article sur une publication Hashnode (API GraphQL, jeton personnel),
 * avec l'adresse de l'original (originalArticleURL) : Hashnode en fait le lien canonique.
 */
class HashnodeClient
{
    private const string ENDPOINT = 'https://gql.hashnode.com';

    private const string PUBLISH = <<<'GRAPHQL'
        mutation PublishPost($input: PublishPostInput!) {
          publishPost(input: $input) { post { id url } }
        }
        GRAPHQL;

    private const string UPDATE = <<<'GRAPHQL'
        mutation UpdatePost($input: UpdatePostInput!) {
          updatePost(input: $input) { post { id url } }
        }
        GRAPHQL;

    private const string PUBLICATIONS = <<<'GRAPHQL'
        query MyPublications {
          me { publications(first: 20) { edges { node { id title url } } } }
        }
        GRAPHQL;

    /**
     * Les publications (blogs) du compte du jeton : de quoi trouver l'identifiant sans le chercher.
     *
     * @return list<array{id: string, title: string, url: string}>
     *
     * @throws RuntimeException Jeton refusé (message de Hashnode).
     */
    public function publications(string $token): array
    {
        $response = Http::withHeaders(['Authorization' => $token])->timeout(15)->post(self::ENDPOINT, ['query' => self::PUBLICATIONS]);
        $errors = $response->json('errors');

        if ($response->failed() || ! empty($errors)) {
            throw new RuntimeException('Hashnode : '.($errors[0]['message'] ?? "erreur {$response->status()}"));
        }

        return collect($response->json('data.me.publications.edges') ?? [])
            ->map(fn (array $edge): array => [
                'id' => (string) ($edge['node']['id'] ?? ''),
                'title' => (string) ($edge['node']['title'] ?? ''),
                'url' => (string) ($edge['node']['url'] ?? ''),
            ])
            ->filter(fn (array $publication): bool => $publication['id'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array{title: string, markdown: string, description: string|null, canonical_url: string, tags: list<string>, cover_url: string|null}  $article
     * @return array{id: string, url: string}
     *
     * @throws RuntimeException L'API a refusé l'article (message de Hashnode).
     */
    public function publish(string $token, string $publicationId, array $article, ?string $externalId = null): array
    {
        $input = array_filter([
            'title' => $article['title'],
            'subtitle' => $article['description'] === null ? null : Str::limit($article['description'], 150, ''),
            'contentMarkdown' => $article['markdown'],
            'originalArticleURL' => $article['canonical_url'],
            'tags' => collect($article['tags'])
                ->map(fn (string $tag): array => ['slug' => Str::slug($tag), 'name' => $tag])
                ->filter(fn (array $tag): bool => $tag['slug'] !== '')
                ->unique('slug')
                ->take(5)
                ->values()
                ->all(),
            'coverImageOptions' => $article['cover_url'] === null ? null : ['coverImageURL' => $article['cover_url']],
        ], fn (mixed $value): bool => $value !== null && $value !== []);

        $variables = ['input' => $externalId === null
            ? [...$input, 'publicationId' => $publicationId]
            : [...$input, 'id' => $externalId]];

        $response = Http::withHeaders(['Authorization' => $token])->timeout(20)->post(self::ENDPOINT, [
            'query' => $externalId === null ? self::PUBLISH : self::UPDATE,
            'variables' => $variables,
        ]);

        $errors = $response->json('errors');

        if ($response->failed() || ! empty($errors)) {
            throw new RuntimeException('Hashnode : '.($errors[0]['message'] ?? "erreur {$response->status()}"));
        }

        $post = $response->json($externalId === null ? 'data.publishPost.post' : 'data.updatePost.post');

        return ['id' => (string) ($post['id'] ?? ''), 'url' => (string) ($post['url'] ?? '')];
    }
}

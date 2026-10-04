<?php

namespace App\Concerns;

use App\Enums\PublicationStatus;
use App\Http\Requests\Admin\PostImageRequest;
use App\Http\Requests\Admin\PostRequest;
use App\Jobs\TranslatePostTag;
use App\Models\Post;
use App\Models\PostSeries;
use App\Models\PostTag;
use Illuminate\Support\Facades\Storage;

/**
 * Enregistrement d'un article (champs, tags, couverture) partagé par l'administration et l'API.
 */
trait SavesPosts
{
    /**
     * Les champs de l'article. Publié sans date, il paraît tout de suite.
     *
     * @return array<string, mixed>
     */
    private function postAttributes(PostRequest $request, ?Post $post = null): array
    {
        $attributes = $request->safe()->except(['cover', 'tags', 'series']);
        $attributes['title'] = array_filter($attributes['title']);
        $attributes['excerpt'] = array_filter($attributes['excerpt'] ?? []);
        $attributes['body'] = array_filter($attributes['body']);

        if ($attributes['status'] === PublicationStatus::Published->value && blank($attributes['published_at'] ?? null)) {
            $attributes['published_at'] = $post?->published_at ?? now();
        }

        return $attributes;
    }

    /**
     * Rattache les tags par leur nom, en créant ceux qui n'existent pas encore ;
     * le nom anglais d'un nouveau tag est traduit en tâche de fond.
     */
    private function syncTags(Post $post, PostRequest $request): void
    {
        $ids = collect($request->validated('tags', []))
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->unique(fn (string $name): string => str($name)->slug()->toString())
            ->map(function (string $name): int {
                $tag = PostTag::query()->firstOrCreate(
                    ['slug' => str($name)->slug()->toString()],
                    ['name' => ['fr' => $name, 'en' => $name]],
                );

                if ($tag->wasRecentlyCreated) {
                    TranslatePostTag::dispatch($tag);
                }

                return $tag->id;
            });

        $post->tags()->sync($ids->all());
    }

    /**
     * Range l'article dans la série nommée (créée au besoin, comme un tag), ou l'en sort
     * quand le nom est vide. Sans le champ, la série ne change pas (client partiel de l'API).
     */
    private function syncSeries(Post $post, PostRequest $request): void
    {
        if (! $request->exists('series')) {
            return;
        }

        $name = trim((string) $request->validated('series'));

        if ($name === '') {
            $post->update(['post_series_id' => null, 'series_position' => null]);

            return;
        }

        $series = PostSeries::query()->firstOrCreate(
            ['slug' => str($name)->slug()->toString()],
            ['name' => ['fr' => $name, 'en' => $name]],
        );

        $post->update(['post_series_id' => $series->id, 'series_position' => $request->validated('series_position')]);
    }

    private function syncCover(Post $post, PostRequest $request): void
    {
        if ($request->hasFile('cover')) {
            $post->addMediaFromRequest('cover')->toMediaCollection('cover');
        }
    }

    /** Image insérée dans le contenu depuis l'éditeur ; renvoie son adresse publique. */
    private function storeContentImage(PostImageRequest $request): string
    {
        $disk = config('media-library.disk_name');

        return Storage::disk($disk)->url($request->file('image')->store('blog', $disk));
    }

    /** @return array<string, mixed> */
    private function postForEditing(Post $post): array
    {
        return [
            ...$post->toArray(),
            // Au format du champ datetime-local, dans le fuseau de l'application.
            'published_at' => $post->published_at?->format('Y-m-d\TH:i'),
            'tags' => $post->tags->map(fn (PostTag $tag): string => $tag->getTranslation('name', 'fr'))->values(),
            'series' => $post->series?->getTranslation('name', 'fr'),
            'cover_url' => $post->getFirstMediaUrl('cover') ?: null,
        ];
    }
}

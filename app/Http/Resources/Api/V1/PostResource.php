<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Post;
use App\Models\PostTag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Post
 */
class PostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            /**
             * L'anglais est facultatif : absent, le site affiche le français.
             *
             * @var array{fr: string, en?: string}
             */
            'title' => $this->getTranslations('title'),
            /** @var array{fr?: string, en?: string}|null */
            'excerpt' => $this->getTranslations('excerpt') ?: null,
            /**
             * Contenu en HTML, nettoyé par le serveur (balises de l'éditeur Tiptap de l'admin).
             *
             * @var array{fr: string, en?: string}
             */
            'body' => $this->getTranslations('body'),
            /** Temps de lecture estimé, en minutes. */
            'reading_minutes' => $this->reading_minutes,
            'is_featured' => $this->is_featured,
            /** `draft` ou `published`. */
            'status' => $this->status,
            /** Date de publication ; dans le futur, l'article est programmé. */
            'published_at' => $this->published_at,
            /** L'article est visible sur le site (publié et date passée). */
            'is_live' => $this->isPublished(),
            'cover_url' => $this->getFirstMediaUrl('cover') ?: null,
            /** @var list<string> */
            'tags' => $this->whenLoaded('tags', fn () => $this->tags
                ->map(fn (PostTag $tag): string => $tag->getTranslation('name', 'fr'))
                ->values()),
            /**
             * Nom français de la série de l'article (`null` hors série). À l'écriture, le champ
             * `series` range l'article dans la série de ce nom (créée au besoin) ; vide, il l'en sort.
             */
            'series' => $this->series?->getTranslation('name', 'fr'),
            /** Place de l'article dans sa série (1, 2, 3…). */
            'series_position' => $this->series_position,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

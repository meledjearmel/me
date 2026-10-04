<?php

namespace App\Http\Resources\Public;

use App\Models\Post;
use App\Services\PostContent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Article du blog dans la langue du visiteur (le français sert de repli). Le contenu et son
 * sommaire ne sont joints que sur la page de l'article (withBody).
 *
 * @mixin Post
 */
class PostResource extends JsonResource
{
    private bool $withBody = false;

    public function withBody(): static
    {
        $this->withBody = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        $translated = filled($this->getTranslation('body', $locale, false));

        $data = [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->getTranslation('title', $locale),
            'excerpt' => $this->getTranslation('excerpt', $locale) ?: null,
            'reading_minutes' => $this->reading_minutes,
            'is_featured' => $this->is_featured,
            'published_at' => $this->published_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'cover_url' => $this->getFirstMediaUrl('cover') ?: null,
            /** Langue réelle du contenu : `fr` quand l'article n'est pas traduit. */
            'content_locale' => $translated ? $locale : config('app.fallback_locale'),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag): array => [
                'slug' => $tag->slug,
                'name' => $tag->getTranslation('name', $locale),
            ])->values()),
        ];

        if ($this->withBody) {
            ['html' => $html, 'toc' => $toc] = app(PostContent::class)->forReading((string) $this->getTranslation('body', $locale));
            $data['body'] = $html;
            $data['toc'] = $toc;
        }

        return $data;
    }
}

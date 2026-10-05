<?php

namespace App\Http\Resources\Public;

use App\Models\Project;
use App\Services\PostMentions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Project */
class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'title' => $this->getTranslation('title', $locale),
            'slug' => $this->slug,
            'tagline' => $this->getTranslation('tagline', $locale) ?: null,
            'role' => $this->getTranslation('role', $locale) ?: null,
            'client' => $this->getTranslation('client', $locale) ?: null,
            'platform' => $this->getTranslation('platform', $locale) ?: null,
            'context' => $this->getTranslation('context', $locale),
            'challenges' => $this->getTranslation('challenges', $locale) ?: null,
            'realization' => $this->getTranslation('realization', $locale),
            'decisions' => collect($this->decisions ?? [])->map(fn (array $decision): array => [
                'choice' => $decision['choice'][$locale] ?? $decision['choice']['fr'] ?? '',
                'reason' => $decision['reason'][$locale] ?? $decision['reason']['fr'] ?? '',
            ])->values(),
            'started_on' => $this->started_on?->format('Y-m'),
            'ended_on' => $this->ended_on?->format('Y-m'),
            'team_size' => $this->team_size,
            'result' => $this->getTranslation('result', $locale),
            'key_figures' => collect($this->key_figures ?? [])->map(fn (array $figure): array => [
                'value' => $figure['value'],
                'label' => $figure['label'][$locale] ?? $figure['label']['fr'] ?? '',
            ])->values(),
            'accent_color' => $this->accent_color,
            'repo_url' => $this->repo_url,
            'demo_url' => $this->demo_url,
            'is_featured' => $this->is_featured,
            'is_open_source' => $this->is_open_source,
            'domains' => DomainResource::collection($this->whenLoaded('domains')),
            'technologies' => TechnologyResource::collection($this->whenLoaded('technologies')),
            'cover_url' => $this->getFirstMediaUrl('cover') ?: null,
            'gallery_urls' => $this->getMedia('gallery')->map->getUrl()->values(),
            /** Cartes des mentions `@[…](type:id)` du récit, par « type:id ». */
            'mentions' => (object) app(PostMentions::class)->cardsInText(
                $locale,
                $this->getTranslation('context', $locale),
                $this->getTranslation('challenges', $locale),
                $this->getTranslation('realization', $locale),
                $this->getTranslation('result', $locale),
            ),
            'related_projects' => static::collection($this->whenLoaded('relatedProjects')),
        ];
    }
}

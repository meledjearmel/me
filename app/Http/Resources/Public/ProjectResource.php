<?php

namespace App\Http\Resources\Public;

use App\Models\Project;
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
            'context' => $this->getTranslation('context', $locale),
            'realization' => $this->getTranslation('realization', $locale),
            'result' => $this->getTranslation('result', $locale),
            'accent_color' => $this->accent_color,
            'repo_url' => $this->repo_url,
            'demo_url' => $this->demo_url,
            'is_featured' => $this->is_featured,
            'is_open_source' => $this->is_open_source,
            'domains' => DomainResource::collection($this->whenLoaded('domains')),
            'technologies' => TechnologyResource::collection($this->whenLoaded('technologies')),
            'cover_url' => $this->getFirstMediaUrl('cover') ?: null,
            'gallery_urls' => $this->getMedia('gallery')->map->getUrl()->values(),
            'related_projects' => static::collection($this->whenLoaded('relatedProjects')),
        ];
    }
}

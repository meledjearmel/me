<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    /** @var array<int, int>|null */
    private ?array $relatedProjectIds = null;

    /**
     * Ajoute les projets liés, fournis à la lecture d'un projet seul.
     *
     * @param  array<int, int>  $relatedProjectIds
     */
    public function withRelatedProjectIds(array $relatedProjectIds): static
    {
        $this->relatedProjectIds = $relatedProjectIds;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->getTranslations('title'),
            'context' => $this->getTranslations('context'),
            'realization' => $this->getTranslations('realization'),
            'result' => $this->getTranslations('result'),
            'accent_color' => $this->accent_color,
            'repo_url' => $this->repo_url,
            'demo_url' => $this->demo_url,
            'is_featured' => $this->is_featured,
            'is_open_source' => $this->is_open_source,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'cover_url' => $this->getFirstMediaUrl('cover') ?: null,
            'gallery_urls' => $this->getMedia('gallery')->map->getUrl()->values(),
            'domains' => DomainResource::collection($this->whenLoaded('domains')),
            'job_profiles' => JobProfileResource::collection($this->whenLoaded('jobProfiles')),
            'technologies' => TechnologyResource::collection($this->whenLoaded('technologies')),
            'related_project_ids' => $this->when($this->relatedProjectIds !== null, $this->relatedProjectIds),
        ];
    }
}

<?php

namespace App\Http\Resources\Public;

use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Skill */
class SkillResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'domain' => new DomainResource($this->whenLoaded('domain')),
            'name' => $this->getTranslation('name', $locale),
            'description' => $this->description !== null
                ? $this->getTranslation('description', $locale)
                : null,
            'details' => $this->details !== null && $this->getTranslation('details', $locale) !== ''
                ? $this->getTranslation('details', $locale)
                : null,
            'technologies' => TechnologyResource::collection($this->whenLoaded('technologies')),
        ];
    }
}

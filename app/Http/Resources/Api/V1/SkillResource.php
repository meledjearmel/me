<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Skill
 */
class SkillResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domain_id' => $this->domain_id,
            'domain' => new DomainResource($this->whenLoaded('domain')),
            'name' => $this->getTranslations('name'),
            'description' => $this->getTranslations('description'),
            'details' => $this->getTranslations('details'),
            'technologies' => TechnologyResource::collection($this->whenLoaded('technologies')),
            'sort_order' => $this->sort_order,
            'status' => $this->status,
        ];
    }
}

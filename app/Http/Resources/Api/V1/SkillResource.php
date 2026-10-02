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
            /** @var array{fr: string, en: string} */
            'name' => $this->getTranslations('name'),
            /**
             * Facultatif : objet vide `{}` tant qu'il n'est pas renseigné.
             *
             * @var array{fr?: string, en?: string}
             */
            'description' => $this->getTranslations('description') ?: (object) [],
            /**
             * Facultatif : objet vide `{}` tant qu'il n'est pas renseigné.
             *
             * @var array{fr?: string, en?: string}
             */
            'details' => $this->getTranslations('details') ?: (object) [],
            'technologies' => TechnologyResource::collection($this->whenLoaded('technologies')),
            'sort_order' => $this->sort_order,
            'status' => $this->status,
        ];
    }
}

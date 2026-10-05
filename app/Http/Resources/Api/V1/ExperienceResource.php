<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Experience;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Experience
 */
class ExperienceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company' => $this->company,
            /** @var array{fr: string, en: string} */
            'role' => $this->getTranslations('role'),
            'location' => $this->location,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            /**
             * Facultatif : objet vide `{}` tant qu'il n'est pas renseigné. Peut contenir des mentions
             * `@[App Station](project:12)` (voir `GET /v1/posts/mentions`).
             *
             * @var array{fr?: string, en?: string}
             */
            'description' => $this->getTranslations('description') ?: (object) [],
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'highlights' => $this->whenLoaded('highlights', fn () => $this->highlights->map(fn ($highlight): array => [
                'id' => $highlight->id,
                /** @var array{fr: string, en: string} */
                'text' => $highlight->getTranslations('text'),
                'sort_order' => $highlight->sort_order,
            ])),
        ];
    }
}

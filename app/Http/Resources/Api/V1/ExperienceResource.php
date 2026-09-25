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
            'role' => $this->getTranslations('role'),
            'location' => $this->location,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'description' => $this->getTranslations('description'),
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'highlights' => $this->whenLoaded('highlights', fn () => $this->highlights->map(fn ($highlight): array => [
                'id' => $highlight->id,
                'text' => $highlight->getTranslations('text'),
                'sort_order' => $highlight->sort_order,
            ])),
        ];
    }
}

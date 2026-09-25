<?php

namespace App\Http\Resources\Public;

use App\Models\Experience;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Experience */
class ExperienceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'company' => $this->company,
            'role' => $this->getTranslation('role', $locale),
            'location' => $this->location,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'description' => $this->description !== null
                ? $this->getTranslation('description', $locale)
                : null,
            'highlights' => $this->whenLoaded('highlights', fn () => $this->highlights
                ->sortBy('sort_order')
                ->map(fn ($highlight) => $highlight->getTranslation('text', $locale))
                ->values()),
        ];
    }
}

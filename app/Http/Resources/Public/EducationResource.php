<?php

namespace App\Http\Resources\Public;

use App\Models\Education;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Education */
class EducationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'institution' => $this->institution,
            'degree' => $this->getTranslation('degree', $locale),
            'field' => $this->getTranslation('field', $locale),
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'description' => $this->description !== null
                ? $this->getTranslation('description', $locale)
                : null,
        ];
    }
}

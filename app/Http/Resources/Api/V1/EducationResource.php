<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Education;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Education
 */
class EducationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution' => $this->institution,
            'degree' => $this->getTranslations('degree'),
            'field' => $this->getTranslations('field'),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'description' => $this->getTranslations('description'),
            'sort_order' => $this->sort_order,
            'status' => $this->status,
        ];
    }
}

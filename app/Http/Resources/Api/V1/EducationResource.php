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
            /** @var array{fr: string, en: string} */
            'degree' => $this->getTranslations('degree'),
            /** @var array{fr: string, en: string} */
            'field' => $this->getTranslations('field'),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            /**
             * Facultatif : objet vide `{}` tant qu'il n'est pas renseigné.
             *
             * @var array{fr?: string, en?: string}
             */
            'description' => $this->getTranslations('description') ?: (object) [],
            'sort_order' => $this->sort_order,
            'status' => $this->status,
        ];
    }
}

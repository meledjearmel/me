<?php

namespace App\Http\Resources\Api\V1;

use App\Models\AppointmentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AppointmentType
 */
class AppointmentTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            /** @var array{fr: string, en: string} */
            'name' => $this->getTranslations('name'),
            /** @var array{fr?: string, en?: string} */
            'description' => $this->getTranslations('description'),
            'duration_minutes' => $this->duration_minutes,
            /**
             * Lieux possibles : `video`, `phone`, `whatsapp`, `in_person`.
             *
             * @var list<string>
             */
            'locations' => $this->locations->map->value->values()->all(),
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}

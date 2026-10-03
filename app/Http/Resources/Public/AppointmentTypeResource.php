<?php

namespace App\Http\Resources\Public;

use App\Models\AppointmentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AppointmentType */
class AppointmentTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getTranslation('name', app()->getLocale()),
            'description' => $this->getTranslation('description', app()->getLocale()) ?: null,
            'duration_minutes' => $this->duration_minutes,
            'locations' => $this->locations->map->value->values()->all(),
        ];
    }
}

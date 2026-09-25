<?php

namespace App\Http\Resources\Public;

use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Domain */
class DomainResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'label' => $this->getTranslation('label', app()->getLocale()),
            'color' => $this->color,
            'icon' => $this->icon,
        ];
    }
}

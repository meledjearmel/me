<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Domain
 */
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
            'label' => $this->getTranslations('label'),
            'color' => $this->color,
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
        ];
    }
}

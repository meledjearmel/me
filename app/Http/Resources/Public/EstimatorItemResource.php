<?php

namespace App\Http\Resources\Public;

use App\Models\EstimatorItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EstimatorItem */
class EstimatorItemResource extends JsonResource
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
            // Types de projet : forfait estimé ou facturation à l'heure.
            'billing' => $this->billing?->value,
            // Options : types de projet auxquels elles sont réservées (vide : tous).
            'project_type_ids' => $this->whenLoaded('projectTypes', fn (): array => $this->projectTypes->modelKeys(), []),
        ];
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use App\Models\EstimatorItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EstimatorItem
 */
class EstimatorItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            /** Type de projet (`project_type`, la base) ou option (`feature`, qui s'ajoute). */
            'kind' => $this->kind,
            /** @var array{fr: string, en: string} */
            'name' => $this->getTranslations('name'),
            /** @var array{fr?: string, en?: string} */
            'description' => $this->getTranslations('description'),
            /** Heures de travail au minimum, hors marge pour imprévus. */
            'hours_min' => $this->hours_min,
            /** Heures de travail au maximum, hors marge pour imprévus. */
            'hours_max' => $this->hours_max,
            /** Options : `hours` (heures min/max) ou `percent` (pourcentage des heures du projet). */
            'pricing' => $this->pricing,
            /** Options en pourcentage : ce qu'elles ajoutent aux heures du projet, en %. */
            'percent' => $this->percent,
            /** Types de projet : `fixed` (forfait estimé) ou `hourly` (facturé à l'heure) ; null pour une option. */
            'billing' => $this->billing,
            /**
             * Options : types de projet auxquels elles sont réservées (vide : proposées pour tous).
             *
             * @var list<int>
             */
            'project_type_ids' => $this->whenLoaded('projectTypes', fn (): array => $this->projectTypes->modelKeys(), []),
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}

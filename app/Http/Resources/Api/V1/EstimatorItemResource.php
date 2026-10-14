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
            /** Grille tarifaire suivie (voir `/estimator-rates`) ; null : taux propres ou taux global. */
            'estimator_rate_id' => $this->estimator_rate_id,
            /**
             * Taux horaires propres par devise, prioritaires sur la grille puis sur le taux global ;
             * null si aucun. Ignorés pour une option en pourcentage (taux du type de projet).
             *
             * @var array{CHF?: int, EUR?: int, USD?: int, XOF?: int}|null
             */
            'hourly_rates' => $this->hourly_rates,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}

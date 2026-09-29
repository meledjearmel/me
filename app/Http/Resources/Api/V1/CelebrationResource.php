<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Celebration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Celebration
 */
class CelebrationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->getTranslations('message'),
            'button_label' => $this->getTranslations('button_label'),
            /** Ce qui est célébré, en français et adressé à Armel : sert à rédiger la notification push. */
            'congratulated_for' => $this->congratulated_for,
            'is_active' => $this->is_active,
            'starts_at' => $this->starts_at?->toDateString(),
            'ends_at' => $this->ends_at?->toDateString(),
            'weight' => $this->weight,
            /** Pourcentage de visites (1 à 100) qui voient la surprise, une fois par session au plus. */
            'chance_percent' => $this->chance_percent,
            /** Secondes avant l'apparition de la bulle. */
            'delay_seconds' => $this->delay_seconds,
            /** Secondes d'affichage sans interaction avant que la bulle ne reparte. */
            'display_seconds' => $this->display_seconds,
            /** Félicitations reçues des visiteurs (lecture seule). */
            'congratulations_count' => $this->congratulations_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

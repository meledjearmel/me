<?php

namespace App\Http\Resources\Api\V1;

use App\Models\EstimatorRate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EstimatorRate
 */
class EstimatorRateResource extends JsonResource
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
            /**
             * Taux horaire par devise ; une devise absente suit le taux global des réglages.
             *
             * @var array{CHF?: int, EUR?: int, USD?: int, XOF?: int}
             */
            'hourly_rates' => $this->rates(),
            'sort_order' => $this->sort_order,
            /** Éléments de l'estimateur rattachés à cette grille. */
            'items_count' => $this->whenCounted('items'),
        ];
    }
}

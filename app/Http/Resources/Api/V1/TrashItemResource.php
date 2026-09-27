<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fusionne des éléments issus de plusieurs modèles ; aucun modèle unique ne
 * la sous-tend, d'où le mixin générique pour la doc OpenAPI.
 *
 * @mixin \stdClass
 *
 * @property int $id
 * @property string $type
 * @property string $label
 * @property string $title
 * @property string|null $deleted_at
 */
class TrashItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->resource['id'],
            'type' => $this->resource['type'],
            'label' => $this->resource['label'],
            'title' => $this->resource['title'],
            'deleted_at' => $this->resource['deleted_at'],
        ];
    }
}

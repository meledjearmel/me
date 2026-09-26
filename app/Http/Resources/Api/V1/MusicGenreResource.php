<?php

namespace App\Http\Resources\Api\V1;

use App\Models\MusicGenre;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MusicGenre
 */
class MusicGenreResource extends JsonResource
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
            'sort_order' => $this->sort_order,
        ];
    }
}

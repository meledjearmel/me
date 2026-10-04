<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PostSeries;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PostSeries
 */
class PostSeriesResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            /** @var array{fr: string, en: string} */
            'name' => $this->getTranslations('name'),
            /** Nombre d'articles de la série (publiés ou non). */
            'posts_count' => $this->whenCounted('posts'),
        ];
    }
}

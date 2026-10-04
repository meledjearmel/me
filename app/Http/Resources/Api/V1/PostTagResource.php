<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PostTag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PostTag
 */
class PostTagResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            /** Utilisé dans les liens de filtre du blog ; il ne change pas quand on renomme le tag. */
            'slug' => $this->slug,
            /** @var array{fr: string, en?: string} */
            'name' => $this->getTranslations('name'),
            /** Nombre d'articles qui portent ce tag. */
            'posts_count' => $this->whenCounted('posts'),
        ];
    }
}

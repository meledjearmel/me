<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PostComment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PostComment
 */
class PostCommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author_name' => $this->author_name,
            /** Jamais affiché sur le site. */
            'author_email' => $this->author_email,
            'body' => $this->body,
            /** Langue de la page où le commentaire a été écrit (`fr` ou `en`). */
            'locale' => $this->locale,
            /** `pending`, `approved` (visible sur le site) ou `rejected`. */
            'status' => $this->status,
            /** L'article commenté. */
            'post' => $this->whenLoaded('post', fn (): array => [
                'id' => $this->post->id,
                'slug' => $this->post->slug,
                'title' => $this->post->getTranslation('title', 'fr'),
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

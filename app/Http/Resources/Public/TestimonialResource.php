<?php

namespace App\Http\Resources\Public;

use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Jamais d'e-mail ici : il sert à la modération, pas à l'affichage public.
 *
 * @mixin Testimonial
 */
class TestimonialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author_name' => $this->author_name,
            'author_role' => $this->author_role,
            'content' => $this->getTranslation('content', app()->getLocale()),
        ];
    }
}

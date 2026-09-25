<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
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
            'author_email' => $this->author_email,
            'author_role' => $this->author_role,
            'content' => $this->getTranslations('content'),
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'project' => $this->whenLoaded('project', fn (): ?array => $this->project === null ? null : [
                'id' => $this->project->id,
                'slug' => $this->project->slug,
                'title' => $this->project->getTranslations('title'),
            ]),
            'submitted_at' => $this->submitted_at,
        ];
    }
}

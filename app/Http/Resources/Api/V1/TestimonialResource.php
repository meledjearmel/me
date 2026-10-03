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
            /** @var array{fr: string, en: string} */
            'content' => $this->getTranslations('content'),
            /**
             * Phrase d'accroche affichée en grand sur les cartes du site.
             *
             * @var array{fr?: string, en?: string}
             */
            'highlight' => $this->getTranslations('highlight'),
            /** @var array{fr?: string, en?: string} */
            'video_transcript' => $this->getTranslations('video_transcript'),
            /**
             * Vidéo de l'avis. `duration` (secondes), `width` et `height` restent nuls
             * tant que la vidéo n'a pas été traitée.
             */
            'video' => $this->videoData(),
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'project' => $this->whenLoaded('project', fn (): ?array => $this->project === null ? null : [
                'id' => $this->project->id,
                'slug' => $this->project->slug,
                /** @var array{fr: string, en: string} */
                'title' => $this->project->getTranslations('title'),
            ]),
            'experience' => $this->whenLoaded('experience', fn (): ?array => $this->experience === null ? null : [
                'id' => $this->experience->id,
                'company' => $this->experience->company,
                /** @var array{fr: string, en: string} */
                'role' => $this->experience->getTranslations('role'),
            ]),
            'education' => $this->whenLoaded('education', fn (): ?array => $this->education === null ? null : [
                'id' => $this->education->id,
                'institution' => $this->education->institution,
                /** @var array{fr: string, en: string} */
                'degree' => $this->education->getTranslations('degree'),
            ]),
            'submitted_at' => $this->submitted_at,
        ];
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
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
            /** Projets dont parle l'avis (plusieurs possibles). */
            'projects' => $this->whenLoaded('projects', fn (): array => $this->projects->map(fn (Project $project): array => [
                'id' => $project->id,
                'slug' => $project->slug,
                /** @var array{fr: string, en: string} */
                'title' => $project->getTranslations('title'),
            ])->all()),
            /** Expériences dont parle l'avis (plusieurs possibles). */
            'experiences' => $this->whenLoaded('experiences', fn (): array => $this->experiences->map(fn (Experience $experience): array => [
                'id' => $experience->id,
                'company' => $experience->company,
                /** @var array{fr: string, en: string} */
                'role' => $experience->getTranslations('role'),
            ])->all()),
            /** Formations dont parle l'avis (plusieurs possibles). */
            'educations' => $this->whenLoaded('educations', fn (): array => $this->educations->map(fn (Education $education): array => [
                'id' => $education->id,
                'institution' => $education->institution,
                /** @var array{fr: string, en: string} */
                'degree' => $education->getTranslations('degree'),
            ])->all()),
            /**
             * Premier projet rattaché, gardé pour les anciennes versions de l'app.
             *
             * @deprecated Utiliser `projects`.
             */
            'project' => $this->whenLoaded('projects', fn (): ?array => $this->projects->isEmpty() ? null : [
                'id' => $this->projects->first()->id,
                'slug' => $this->projects->first()->slug,
                'title' => $this->projects->first()->getTranslations('title'),
            ]),
            /** Lien d'invitation par lequel l'avis est arrivé. */
            'review_invitation_id' => $this->review_invitation_id,
            'submitted_at' => $this->submitted_at,
        ];
    }
}

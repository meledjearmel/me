<?php

namespace App\Http\Resources\Public;

use App\Enums\ProjectStatus;
use App\Enums\PublicationStatus;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
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
            'highlight' => $this->getTranslation('highlight', app()->getLocale()) ?: null,
            'video_transcript' => $this->getTranslation('video_transcript', app()->getLocale()) ?: null,
            'video' => $this->videoData(),
            'subjects' => $this->subjects(app()->getLocale()),
        ];
    }

    /**
     * Ce dont parle l'avis, avec un lien : la page du projet, ou l'expérience et la formation
     * sur la page À propos. Seuls les contenus publiés apparaissent ; vide si les relations
     * ne sont pas chargées.
     *
     * @return list<array{type: 'project'|'experience'|'education', label: string, url: string}>
     */
    private function subjects(string $locale): array
    {
        if (! $this->relationLoaded('projects')) {
            return [];
        }

        $about = route('about', ['locale' => $locale]);

        return [
            ...$this->projects
                ->filter(fn (Project $project): bool => $project->status === ProjectStatus::Published)
                ->map(fn (Project $project): array => [
                    'type' => 'project',
                    'label' => $project->getTranslation('title', $locale),
                    'url' => route('projects.show', ['locale' => $locale, 'project' => $project->slug]),
                ])->values(),
            ...$this->experiences
                ->filter(fn (Experience $experience): bool => $experience->status === PublicationStatus::Published)
                ->map(fn (Experience $experience): array => [
                    'type' => 'experience',
                    'label' => $experience->company,
                    'url' => $about.'#experience-'.$experience->id,
                ])->values(),
            ...$this->educations
                ->filter(fn (Education $education): bool => $education->status === PublicationStatus::Published)
                ->map(fn (Education $education): array => [
                    'type' => 'education',
                    'label' => $education->institution,
                    'url' => $about.'#education-'.$education->id,
                ])->values(),
        ];
    }
}

<?php

namespace App\Http\Resources\Public;

use App\Enums\ProjectStatus;
use App\Enums\PublicationStatus;
use App\Models\Distinction;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Distinction */
class DistinctionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'title' => $this->getTranslation('title', $locale),
            'issuer' => $this->issuer,
            'description' => $this->description !== null
                ? $this->getTranslation('description', $locale)
                : null,
            'awarded_on' => $this->awarded_on->format('Y-m-d'),
            'url' => $this->url,
            'congratulations' => $this->congratulations_count,
            'context' => $this->context($locale),
        ];
    }

    /**
     * Le contexte publié de la distinction, avec son lien : l'expérience ou la formation sur
     * la page À propos, ou la page du projet. Null s'il n'y en a pas ou s'il n'est pas publié.
     *
     * @return array{type: string, label: string, url: string}|null
     */
    private function context(string $locale): ?array
    {
        $context = $this->relationLoaded('distinguishable') ? $this->distinguishable : null;
        $about = route('about', ['locale' => $locale]);

        return match (true) {
            $context instanceof Experience && $context->status === PublicationStatus::Published => [
                'type' => 'experience',
                'label' => $context->company,
                'url' => $about.'#experience-'.$context->id,
            ],
            $context instanceof Education && $context->status === PublicationStatus::Published => [
                'type' => 'education',
                'label' => $context->institution,
                'url' => $about.'#education-'.$context->id,
            ],
            $context instanceof Project && $context->status === ProjectStatus::Published => [
                'type' => 'project',
                'label' => $context->getTranslation('title', $locale),
                'url' => route('projects.show', ['locale' => $locale, 'project' => $context->slug]),
            ],
            default => null,
        };
    }
}

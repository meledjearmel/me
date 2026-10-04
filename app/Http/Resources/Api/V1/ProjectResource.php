<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    /** @var array<int, int>|null */
    private ?array $relatedProjectIds = null;

    /**
     * Ajoute les projets liés, fournis à la lecture d'un projet seul.
     *
     * @param  array<int, int>  $relatedProjectIds
     */
    public function withRelatedProjectIds(array $relatedProjectIds): static
    {
        $this->relatedProjectIds = $relatedProjectIds;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            /** @var array{fr: string, en: string} */
            'title' => $this->getTranslations('title'),
            /**
             * Accroche du bandeau de la page projet (`null` si absente : le site reprend la première phrase du contexte).
             *
             * @var array{fr?: string, en?: string}|null
             */
            'tagline' => $this->getTranslations('tagline') ?: null,
            /**
             * Rôle tenu sur le projet (fiche d'identité de la page projet).
             *
             * @var array{fr?: string, en?: string}|null
             */
            'role' => $this->getTranslations('role') ?: null,
            /**
             * Client ou cadre du projet, anonymisé (« Organisme public », « Projet personnel »…).
             *
             * @var array{fr?: string, en?: string}|null
             */
            'client' => $this->getTranslations('client') ?: null,
            /**
             * Plateforme (« Web · API REST », « Mobile (iOS · Android) »…).
             *
             * @var array{fr?: string, en?: string}|null
             */
            'platform' => $this->getTranslations('platform') ?: null,
            /**
             * Début du projet, au mois près (AAAA-MM).
             */
            'started_on' => $this->started_on?->format('Y-m'),
            /**
             * Fin du projet (AAAA-MM). `null` avec un début renseigné : projet en cours.
             */
            'ended_on' => $this->ended_on?->format('Y-m'),
            /** Taille de l'équipe, moi compris. */
            'team_size' => $this->team_size,
            /** @var array{fr: string, en: string} */
            'context' => $this->getTranslations('context'),
            /**
             * Défis et contraintes du projet (section facultative de l'étude de cas).
             *
             * @var array{fr?: string, en?: string}|null
             */
            'challenges' => $this->getTranslations('challenges') ?: null,
            /** @var array{fr: string, en: string} */
            'realization' => $this->getTranslations('realization'),
            /**
             * Choix techniques argumentés (6 au plus), dans l'ordre.
             *
             * @var list<array{choice: array{fr: string, en: string}, reason: array{fr: string, en: string}}>
             */
            'decisions' => $this->decisions ?? [],
            /** @var array{fr: string, en: string} */
            'result' => $this->getTranslations('result'),
            /**
             * Chiffres clés affichés sous le résultat (4 au plus), dans l'ordre.
             *
             * @var list<array{value: string, label: array{fr: string, en: string}}>
             */
            'key_figures' => $this->key_figures ?? [],
            'accent_color' => $this->accent_color,
            'repo_url' => $this->repo_url,
            'demo_url' => $this->demo_url,
            'is_featured' => $this->is_featured,
            'is_open_source' => $this->is_open_source,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'cover_url' => $this->getFirstMediaUrl('cover') ?: null,
            /** Galerie : `id` sert à cibler `DELETE /projects/{project}/gallery/{id}`. */
            'gallery' => $this->getMedia('gallery')->map(fn ($media): array => [
                'id' => $media->id,
                'url' => $media->getUrl(),
            ])->values(),
            'domains' => DomainResource::collection($this->whenLoaded('domains')),
            'job_profiles' => JobProfileResource::collection($this->whenLoaded('jobProfiles')),
            'technologies' => TechnologyResource::collection($this->whenLoaded('technologies')),
            'related_project_ids' => $this->when($this->relatedProjectIds !== null, $this->relatedProjectIds),
        ];
    }
}

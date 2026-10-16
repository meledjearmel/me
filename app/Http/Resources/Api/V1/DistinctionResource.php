<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Distinction;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Distinction
 */
class DistinctionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $context = $this->relationLoaded('distinguishable') ? $this->distinguishable : null;

        return [
            'id' => $this->id,
            /** @var array{fr: string, en: string|null} */
            'title' => $this->getTranslations('title'),
            /** Organisme qui a décerné la distinction. */
            'issuer' => $this->issuer,
            /** @var array{fr?: string, en?: string} */
            'description' => $this->getTranslations('description'),
            /** Date d'obtention (AAAA-MM-JJ). */
            'awarded_on' => $this->awarded_on?->toDateString(),
            /** Lien vers une preuve (article, publication). */
            'url' => $this->url,
            /** Contexte : `experience`, `education` ou `project`, `null` sans contexte. */
            'context_type' => $this->contextKey(),
            /** Identifiant de l'expérience, de la formation ou du projet. */
            'context_id' => $this->distinguishable_id,
            /** Intitulé du contexte (entreprise, établissement ou titre du projet). */
            'context_label' => match (true) {
                $context instanceof Experience => $context->company,
                $context instanceof Education => $context->institution,
                $context instanceof Project => $context->getTranslation('title', 'fr'),
                default => null,
            },
            /** Félicitations reçues sur la page À propos. */
            'congratulations_count' => $this->congratulations_count,
            /** `published` : affichée sur la page À propos ; `draft` : masquée. */
            'status' => $this->status,
            /** Client IA qui a rédigé le brouillon via le serveur MCP (« Claude Code »…), sinon null. */
            'drafted_by' => $this->drafted_by,
            'sort_order' => $this->sort_order,
        ];
    }
}

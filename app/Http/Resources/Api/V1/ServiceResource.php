<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Service
 */
class ServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            /** Ancre de l'offre sur la page « Services » (`/services#slug`). */
            'slug' => $this->slug,
            /** @var array{fr: string, en?: string} */
            'title' => $this->getTranslations('title'),
            /** @var array{fr?: string, en?: string} */
            'tagline' => $this->getTranslations('tagline'),
            /** @var array{fr?: string, en?: string} */
            'description' => $this->getTranslations('description'),
            /**
             * Ce qui est compris, un élément par ligne.
             *
             * @var array{fr?: string, en?: string}
             */
            'includes' => $this->getTranslations('includes'),
            /** `estimator` (prix calculé depuis le type de projet lié), `manual` (montants saisis) ou `quote` (sur devis). */
            'price_mode' => $this->price_mode,
            /** Type de projet de l'estimateur : prix calculé et lien « Estimer mon projet ». */
            'estimator_item_id' => $this->estimator_item_id,
            /**
             * Prix « à partir de » saisis par devise (mode `manual`).
             *
             * @var array<string, int>|null
             */
            'manual_prices' => $this->manual_prices,
            /**
             * Projets qui illustrent l'offre ; leurs avis publiés s'affichent avec elle.
             *
             * @var list<int>
             */
            'project_ids' => $this->whenLoaded('projects', fn (): array => $this->projects->modelKeys(), []),
            /** `published` : affichée sur la page publique ; `draft` : masquée. */
            'status' => $this->status,
            /** Client IA qui a rédigé le brouillon via le serveur MCP (« Claude Code »…), sinon null. */
            'drafted_by' => $this->drafted_by,
            'sort_order' => $this->sort_order,
        ];
    }
}

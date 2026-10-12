<?php

namespace App\Http\Resources\Api\V1;

use App\Models\UsesItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UsesItem
 */
class UsesItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            /** Rubrique de la page : `hardware`, `development`, `apps` ou `services`. */
            'category' => $this->category,
            'name' => $this->name,
            /** @var array{fr?: string, en?: string}|null */
            'description' => $this->getTranslations('description') ?: null,
            'url' => $this->url,
            /** `published` : affiché sur la page publique ; `draft` : masqué. */
            'status' => $this->status,
            /** Client IA qui a rédigé le brouillon via le serveur MCP (« Claude Code »…), sinon null. */
            'drafted_by' => $this->drafted_by,
            'sort_order' => $this->sort_order,
        ];
    }
}

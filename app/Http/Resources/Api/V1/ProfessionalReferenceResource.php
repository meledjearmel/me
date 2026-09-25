<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ProfessionalReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProfessionalReference
 */
class ProfessionalReferenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role,
            'company' => $this->company,
            'email' => $this->email,
            'phone' => $this->phone,
            'relationship' => $this->relationship,
            'project' => $this->whenLoaded('project', fn (): ?array => $this->project === null ? null : [
                'id' => $this->project->id,
                'slug' => $this->project->slug,
                'title' => $this->project->getTranslations('title'),
            ]),
            'project_id' => $this->project_id,
            'is_public' => $this->is_public,
            'visible_fields' => $this->visible_fields,
            'notes' => $this->notes,
        ];
    }
}

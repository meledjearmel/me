<?php

namespace App\Http\Resources\Api\V1;

use App\Models\JobProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JobProfile
 */
class JobProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'label' => $this->getTranslations('label'),
            'description' => $this->getTranslations('description'),
            'hero_title' => $this->getTranslations('hero_title'),
            'hero_words' => $this->getTranslations('hero_words'),
            'cv_description' => $this->getTranslations('cv_description'),
            'sort_order' => $this->sort_order,
            'status' => $this->status,
        ];
    }
}

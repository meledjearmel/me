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
            /** CV PDF uploadé par langue pour ce profil métier (`null` si absent : le CV est alors généré, ou repris de l'autre langue). */
            'cv_files' => collect(JobProfile::CV_LOCALES)
                ->mapWithKeys(function (string $locale): array {
                    $media = $this->getFirstMedia(JobProfile::cvFileCollection($locale));

                    return [$locale => $media === null ? null : [
                        'file_name' => $media->file_name,
                        'url' => $media->getUrl(),
                    ]];
                }),
        ];
    }
}

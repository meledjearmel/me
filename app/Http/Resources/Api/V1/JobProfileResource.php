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
            /** @var array{fr: string, en: string} */
            'label' => $this->getTranslations('label'),
            /** @var array{fr: string, en: string} */
            'description' => $this->getTranslations('description'),
            /**
             * Facultatif : objet vide `{}` tant qu'il n'est pas renseigné.
             *
             * @var array{fr?: string, en?: string}
             */
            'hero_title' => $this->getTranslations('hero_title') ?: (object) [],
            /**
             * Facultatif : objet vide `{}` tant qu'il n'est pas renseigné.
             *
             * @var array{fr?: string, en?: string}
             */
            'hero_words' => $this->getTranslations('hero_words') ?: (object) [],
            /** @var array{fr: string, en: string} */
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

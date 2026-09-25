<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Profile
 */
class ProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'cv_last_name' => $this->cv_last_name,
            'cv_first_name' => $this->cv_first_name,
            'headline' => $this->getTranslations('headline'),
            'bio_short' => $this->getTranslations('bio_short'),
            'bio_full' => $this->getTranslations('bio_full'),
            'email' => $this->email,
            'phone' => $this->phone,
            'location' => $this->location,
            'social_links' => $this->social_links,
            'photo_url' => $this->getFirstMediaUrl('photo') ?: null,
            'cv_photo_url' => $this->getFirstMediaUrl('cv_photo') ?: null,
            /** Bande audio du site uploadée (`null` si absente : le lecteur utilise la piste par défaut). */
            'music' => ($music = $this->getFirstMedia('music')) === null ? null : [
                'file_name' => $music->file_name,
                'url' => $music->getUrl(),
            ],
            /** CV PDF uploadé par langue (`null` si absent : le CV est alors généré, ou repris de l'autre langue). */
            'cv_files' => collect(Profile::CV_LOCALES)
                ->mapWithKeys(function (string $locale): array {
                    $media = $this->getFirstMedia(Profile::cvFileCollection($locale));

                    return [$locale => $media === null ? null : [
                        'file_name' => $media->file_name,
                        'url' => $media->getUrl(),
                    ]];
                }),
        ];
    }
}

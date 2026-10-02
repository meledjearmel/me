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
            /** Au plus une notification push de félicitations par motif sur ce nombre de minutes (0 = à chaque envoi). */
            'congratulation_notify_minutes' => $this->congratulation_notify_minutes,
            /** Profil métier dont le CV est téléchargeable sur le site (`null` : le premier publié). */
            'cv_job_profile_id' => $this->cv_job_profile_id,
            /** Source prioritaire du CV téléchargeable : `uploaded` (PDF importé) ou `generated`, l'autre en repli. */
            'cv_source' => $this->cv_source,
            'photo_url' => $this->getFirstMediaUrl('photo') ?: null,
            'cv_photo_url' => $this->getFirstMediaUrl('cv_photo') ?: null,
            /** Bande audio du site uploadée (`null` si absente : le lecteur utilise la piste par défaut). */
            'music' => ($music = $this->getFirstMedia('music')) === null ? null : [
                'file_name' => $music->file_name,
                'url' => $music->getUrl(),
            ],
        ];
    }
}

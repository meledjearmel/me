<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CvDownload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CvDownload
 */
class CvDownloadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            /** Profil métier dont le CV a été servi. */
            'job_profile_id' => $this->job_profile_id,
            /**
             * Libellé du profil métier dans les deux langues (`null` si le profil a été supprimé).
             *
             * @var array{fr: string, en: string}|null
             */
            'job_profile_label' => $this->jobProfile?->getTranslations('label'),
            /**
             * Langue du CV servi.
             *
             * @var 'fr'|'en'
             */
            'locale' => $this->locale,
            /** Source du CV servi : `uploaded` (PDF importé) ou `generated`. */
            'source' => $this->source,
            /** Email laissé par le visiteur (facultatif). */
            'email' => $this->email,
            /** Code ISO du pays (`CI`, `FR`…), d'après la base GeoLite2. */
            'country_code' => $this->country_code,
            'country' => $this->country,
            'city' => $this->city,
            /** Site d'où venait le visiteur à son arrivée (`linkedin.com`…), `null` pour un accès direct. */
            'referrer_host' => $this->referrer_host,
            /** Campagne : `utm_source` ou le raccourci `?ref=`. */
            'utm_source' => $this->utm_source,
            'utm_medium' => $this->utm_medium,
            'utm_campaign' => $this->utm_campaign,
            /** Provenance résumée : la campagne, sinon le site d'origine, sinon `direct`. */
            'origin' => $this->origin(),
            /**
             * Type d'appareil du visiteur.
             *
             * @var 'desktop'|'mobile'|'tablet'|null
             */
            'device' => $this->device,
            'created_at' => $this->created_at,
        ];
    }
}

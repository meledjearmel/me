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
            'job_profile_label' => $this->jobProfile?->getTranslations('label'),
            /** Langue du CV servi : `fr` ou `en`. */
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
            /** `desktop`, `mobile` ou `tablet`. */
            'device' => $this->device,
            'created_at' => $this->created_at,
        ];
    }
}

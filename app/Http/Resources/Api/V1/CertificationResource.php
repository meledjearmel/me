<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Certification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Certification
 */
class CertificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            /** `certification` (examen, souvent vérifiable) ou `course` (formation courte). */
            'kind' => $this->kind,
            /** @var array{fr: string, en: string} */
            'name' => $this->getTranslations('name'),
            /** Organisme qui délivre la certification ou la formation. */
            'issuer' => $this->issuer,
            /** Date d'obtention (AAAA-MM-JJ). */
            'issued_on' => $this->issued_on?->toDateString(),
            /** Date d'expiration (AAAA-MM-JJ), `null` si elle n'expire pas. */
            'expires_on' => $this->expires_on?->toDateString(),
            'credential_id' => $this->credential_id,
            /** Lien de vérification de la certification. */
            'credential_url' => $this->credential_url,
            'badge_url' => $this->getFirstMediaUrl('badge') ?: null,
            /** `published` : affichée sur la page publique ; `draft` : masquée. */
            'status' => $this->status,
            'sort_order' => $this->sort_order,
        ];
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ReviewInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ReviewInvitation
 */
class ReviewInvitationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            /** Langue du lien et du formulaire : `fr` ou `en`. */
            'locale' => $this->locale,
            /** Le lien personnel à envoyer : il ouvre le formulaire d'avis prérempli. */
            'url' => $this->url,
            /** `pending` (lien valable), `used` (avis reçu) ou `expired`. */
            'status' => $this->status,
            'project_id' => $this->project_id,
            'experience_id' => $this->experience_id,
            'education_id' => $this->education_id,
            /** Ce dont parle l'avis, en français ; null pour un avis général. */
            'subject' => $this->subjectLabel('fr'),
            'note' => $this->note,
            /** Avis reçu par ce lien, à relire dans les avis. */
            'testimonial_id' => $this->testimonial_id,
            'expires_at' => $this->expires_at,
            'used_at' => $this->used_at,
            'created_at' => $this->created_at,
        ];
    }
}

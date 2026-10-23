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
            /** Le lien à envoyer : il ouvre le formulaire d'avis prérempli. */
            'url' => $this->url,
            /** `pending` (lien valable), `used` (avis reçu ou limite atteinte) ou `expired`. */
            'status' => $this->status,
            /** Lien collectif : plusieurs personnes peuvent laisser leur avis par ce même lien. */
            'collective' => $this->isCollective(),
            /** Nombre d'avis au plus (1 pour un lien personnel) ; null = sans limite. */
            'max_uses' => $this->max_uses,
            /** Nombre d'avis déjà reçus par ce lien. */
            'uses_count' => $this->uses_count,
            /**
             * Ce dont parle l'avis, en français : projets, expériences et formations (plusieurs possibles).
             *
             * @var list<array{type: 'project'|'experience'|'education', id: int, label: string, slug: string|null}>
             */
            'subjects' => $this->subjectsFor('fr'),
            /** Les mêmes rattachements en une ligne ; null pour un avis général. */
            'subject' => $this->subjectLabel('fr'),
            'note' => $this->note,
            /**
             * Avis reçus par ce lien, à relire dans les avis.
             *
             * @var list<int>
             */
            'testimonial_ids' => $this->testimonials->modelKeys(),
            /**
             * Premier avis reçu, gardé pour les anciennes versions de l'app.
             *
             * @deprecated Utiliser `testimonial_ids`.
             */
            'testimonial_id' => $this->testimonials->first()?->id,
            'expires_at' => $this->expires_at,
            'used_at' => $this->used_at,
            'created_at' => $this->created_at,
        ];
    }
}

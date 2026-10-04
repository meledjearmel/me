<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Jamais de jeton ici : il sert de clé aux liens de confirmation et de désinscription.
 *
 * @mixin Subscriber
 */
class SubscriberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            /** Langue des emails : `fr` ou `en`. */
            'locale' => $this->locale,
            /**
             * `active` (confirmé, reçoit les articles), `pending` (en attente de confirmation)
             * ou `unsubscribed` (désinscrit).
             */
            'status' => match (true) {
                $this->unsubscribed_at !== null => 'unsubscribed',
                $this->confirmed_at !== null => 'active',
                default => 'pending',
            },
            'confirmed_at' => $this->confirmed_at,
            'unsubscribed_at' => $this->unsubscribed_at,
            'created_at' => $this->created_at,
        ];
    }
}

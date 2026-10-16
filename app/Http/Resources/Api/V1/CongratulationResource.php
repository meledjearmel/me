<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Congratulation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Congratulation
 */
class CongratulationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            /** `about` (carte « Distinction » de la page À propos) ou `surprise`. */
            'source' => $this->source,
            /** Surprise d'origine ; `null` pour la page À propos ou si la surprise a été supprimée. */
            'celebration_id' => $this->celebration_id,
            /** Distinction félicitée (source `about`) ; `null` pour une surprise ou si elle a été supprimée. */
            'distinction_id' => $this->distinction_id,
            /** Motif lisible, figé au moment du clic (message FR de la surprise ou intitulé FR de la distinction). */
            'reason' => $this->reason,
            /** Nombre de clics regroupés dans cet envoi. */
            'count' => $this->count,
            /** Langue du visiteur. */
            'locale' => $this->locale,
            'created_at' => $this->created_at,
        ];
    }
}

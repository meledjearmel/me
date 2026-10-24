<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Proposal
 */
class ProposalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            /** `upwork`, `malt`, `fiverr`, `freelancer`, `linkedin` ou `other`. */
            'platform' => $this->platform,
            /** Langue de la proposition : `fr` ou `en`. */
            'locale' => $this->locale,
            'job_title' => $this->job_title,
            /** Prénom ou nom du client (souvent trouvé dans les avis de son historique). */
            'client_name' => $this->client_name,
            'job_url' => $this->job_url,
            /** Texte de l'annonce, questions du client comprises. */
            'job_posting' => $this->job_posting,
            /** La proposition, telle qu'envoyée (sert d'exemple à l'IA). */
            'body' => $this->body,
            /** Réponses aux questions de l'offre, chacune précédée de sa question. */
            'screening_answers' => $this->screening_answers,
            /** Tarif ou montant proposé, en texte libre (ex. « 40 $/h », « 1 200 € au forfait »). */
            'bid' => $this->bid,
            /** Connects Upwork dépensés. */
            'connects' => $this->connects,
            'status' => $this->status,
            'notes' => $this->notes,
            /** Client IA qui a rédigé le brouillon, s'il y en a un. */
            'drafted_by' => $this->drafted_by,
            /** Posée au premier statut qui n'est plus un brouillon. */
            'sent_at' => $this->sent_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

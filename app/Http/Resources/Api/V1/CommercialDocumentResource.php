<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\CommercialDocumentType;
use App\Models\CommercialDocument;
use App\Services\CommercialDocuments;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CommercialDocument
 */
class CommercialDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            /** `quote` (devis, ADEV-DEV), `proforma` (ADEV-PRO) ou `invoice` (facture, ADEV-FAC). */
            'type' => $this->type,
            /** `draft`, `issued`, `accepted`, `declined`, `paid` ou `cancelled`. */
            'status' => $this->status,
            /** Numéro attribué à l'émission (ex. `ADEV-DEV-2026-001`) ; null pour un brouillon. */
            'number' => $this->number,
            'engagement_id' => $this->engagement_id,
            /** Document d'origine (le devis d'une proforma ou d'une facture). */
            'source_id' => $this->source_id,
            'locale' => $this->locale,
            'client_name' => $this->client_name,
            'client_email' => $this->client_email,
            'client_company' => $this->client_company,
            'client_address' => $this->client_address,
            'title' => $this->title,
            'currency' => $this->currency,
            /**
             * `unit` : `hour`, `day` ou `flat` ; montants en unités de la devise.
             *
             * @var list<array{label: string, description: string|null, quantity: float, unit: string, unit_price: int}>
             */
            'lines' => $this->lines,
            /** Somme des lignes, hors taxes (TVA non applicable). */
            'total' => $this->total,
            'deposit_percent' => $this->deposit_percent,
            'deposit_amount' => $this->depositAmount(),
            'payment_terms' => $this->payment_terms,
            'notes' => $this->notes,
            'issued_at' => $this->issued_at?->toDateString(),
            /** Devis : fin de validité. */
            'valid_until' => $this->valid_until?->toDateString(),
            /** Proforma et facture : échéance de paiement. */
            'due_at' => $this->due_at?->toDateString(),
            /** Devis émis : lien privé où le client le consulte et l'accepte. */
            'public_url' => $this->type === CommercialDocumentType::Quote && ! $this->isDraft() ? app(CommercialDocuments::class)->publicUrl($this->resource) : null,
            'sent_at' => $this->sent_at,
            'accepted_at' => $this->accepted_at,
            'declined_at' => $this->declined_at,
            'paid_at' => $this->paid_at,
            /** Relances déjà envoyées. */
            'reminders_count' => $this->reminders_count,
            'last_reminded_at' => $this->last_reminded_at,
            /** Relance suggérée depuis cette date (devis sans réponse, facture en retard) ; null sinon. */
            'reminder_due_at' => $this->reminder_due_at,
            'created_at' => $this->created_at,
        ];
    }
}

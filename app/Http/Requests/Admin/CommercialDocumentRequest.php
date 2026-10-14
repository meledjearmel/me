<?php

namespace App\Http\Requests\Admin;

use App\Enums\CommercialDocumentType;
use App\Models\EstimatorItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommercialDocumentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isCreation = $this->isMethod('post');

        return [
            // Fixé à la création ; une proforma ou une facture se crée aussi depuis un devis.
            'type' => [$isCreation ? 'required' : 'prohibited', Rule::enum(CommercialDocumentType::class)],
            'engagement_id' => ['nullable', 'integer', Rule::exists('engagements', 'id')->whereNull('deleted_at')],
            'locale' => ['required', 'in:fr,en'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'client_company' => ['nullable', 'string', 'max:255'],
            'client_address' => ['nullable', 'string', 'max:1000'],
            'title' => ['required', 'string', 'max:255'],
            'currency' => ['required', Rule::in(EstimatorItem::CURRENCIES)],
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.label' => ['required', 'string', 'max:255'],
            'lines.*.description' => ['nullable', 'string', 'max:1000'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0', 'max:100000'],
            // hour (heures), day (jours) ou flat (forfait).
            'lines.*.unit' => ['required', 'in:hour,day,flat'],
            'lines.*.unit_price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'deposit_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'payment_terms' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            // Devis : fin de validité ; proforma et facture : échéance.
            'valid_until' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Les champs à enregistrer, lignes remises en forme (quantité en nombre, description vide à null).
     *
     * @return array<string, mixed>
     */
    public function documentData(): array
    {
        return [
            ...$this->safe()->except('type'),
            'lines' => collect($this->validated('lines'))->map(fn (array $line): array => [
                'label' => $line['label'],
                'description' => $line['description'] ?? null,
                'quantity' => (float) $line['quantity'],
                'unit' => $line['unit'],
                'unit_price' => (int) $line['unit_price'],
            ])->values()->all(),
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BillingSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'billing_business_name' => ['nullable', 'string', 'max:255'],
            'billing_legal_form' => ['nullable', 'string', 'max:255'],
            'billing_address' => ['nullable', 'string', 'max:1000'],
            'billing_registration_number' => ['nullable', 'string', 'max:100'],
            'billing_iban' => ['nullable', 'string', 'max:50'],
            'billing_bank' => ['nullable', 'string', 'max:255'],
            'billing_vat_mention' => ['nullable', 'string', 'max:255'],
            'billing_payment_terms' => ['nullable', 'string', 'max:2000'],
            'billing_quote_validity_days' => ['required', 'integer', 'min:1', 'max:365'],
            'billing_invoice_due_days' => ['required', 'integer', 'min:0', 'max:365'],
            'billing_deposit_percent' => ['required', 'integer', 'min:0', 'max:100'],
            // Relances : jours sans réponse pour un devis, jours après l'échéance pour une facture, nombre au plus (0 : aucune). Absents : inchangés.
            'billing_quote_reminder_days' => ['sometimes', 'required', 'integer', 'min:1', 'max:90'],
            'billing_invoice_reminder_days' => ['sometimes', 'required', 'integer', 'min:0', 'max:90'],
            'billing_max_reminders' => ['sometimes', 'required', 'integer', 'min:0', 'max:5'],
        ];
    }
}

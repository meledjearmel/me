<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Admin\BillingSettingsController as AdminBillingSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BillingSettingsRequest;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;

/**
 * @tags Devis et factures
 */
class BillingSettingsController extends Controller
{
    /**
     * Réglages de facturation
     *
     * L'émetteur des documents (nom, statut, adresse, numéro d'entreprise, IBAN, banque), la
     * mention de TVA (vide : « TVA non applicable »), les valeurs par défaut d'un document et les
     * relances (jours sans réponse pour un devis, jours après l'échéance pour une facture, nombre au plus).
     *
     * @response array{billing_business_name: string|null, billing_legal_form: string|null, billing_address: string|null, billing_registration_number: string|null, billing_iban: string|null, billing_bank: string|null, billing_vat_mention: string|null, billing_payment_terms: string|null, billing_quote_validity_days: int, billing_invoice_due_days: int, billing_deposit_percent: int, billing_quote_reminder_days: int, billing_invoice_reminder_days: int, billing_max_reminders: int}
     */
    public function show(): JsonResponse
    {
        return response()->json(SiteSetting::current()->only(AdminBillingSettings::FIELDS));
    }

    /**
     * Modifier les réglages de facturation
     *
     * @response array{billing_business_name: string|null, billing_legal_form: string|null, billing_address: string|null, billing_registration_number: string|null, billing_iban: string|null, billing_bank: string|null, billing_vat_mention: string|null, billing_payment_terms: string|null, billing_quote_validity_days: int, billing_invoice_due_days: int, billing_deposit_percent: int, billing_quote_reminder_days: int, billing_invoice_reminder_days: int, billing_max_reminders: int}
     */
    public function update(BillingSettingsRequest $request): JsonResponse
    {
        $settings = SiteSetting::current();
        $settings->update($request->validated());

        return response()->json($settings->only(AdminBillingSettings::FIELDS));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BillingSettingsRequest;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Ce qui figure sur les devis et factures : l'émetteur, la mention de TVA et les valeurs par défaut. */
class BillingSettingsController extends Controller
{
    /** Les champs de facturation des réglages du site. */
    public const array FIELDS = [
        'billing_business_name',
        'billing_legal_form',
        'billing_address',
        'billing_registration_number',
        'billing_iban',
        'billing_bank',
        'billing_vat_mention',
        'billing_payment_terms',
        'billing_quote_validity_days',
        'billing_invoice_due_days',
        'billing_deposit_percent',
        'billing_quote_reminder_days',
        'billing_invoice_reminder_days',
        'billing_max_reminders',
    ];

    public function edit(): Response
    {
        return Inertia::render('admin/billing-settings/edit', [
            'settings' => SiteSetting::current()->only(self::FIELDS),
        ]);
    }

    public function update(BillingSettingsRequest $request): RedirectResponse
    {
        SiteSetting::current()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Réglages de facturation enregistrés.')]);

        return back();
    }
}

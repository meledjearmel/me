<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ce qui figure sur les devis et factures : l'émetteur (nom, statut, adresse, numéro
     * d'entreprise, IBAN), la mention de TVA et les valeurs par défaut des documents.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('billing_business_name')->nullable();
            $table->string('billing_legal_form')->nullable();
            $table->text('billing_address')->nullable();
            $table->string('billing_registration_number')->nullable();
            $table->string('billing_iban')->nullable();
            $table->string('billing_bank')->nullable();
            $table->string('billing_vat_mention')->nullable();
            $table->text('billing_payment_terms')->nullable();
            $table->unsignedSmallInteger('billing_quote_validity_days')->default(30);
            $table->unsignedSmallInteger('billing_invoice_due_days')->default(30);
            $table->unsignedTinyInteger('billing_deposit_percent')->default(30);
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'billing_business_name', 'billing_legal_form', 'billing_address', 'billing_registration_number',
                'billing_iban', 'billing_bank', 'billing_vat_mention', 'billing_payment_terms',
                'billing_quote_validity_days', 'billing_invoice_due_days', 'billing_deposit_percent',
            ]);
        });
    }
};

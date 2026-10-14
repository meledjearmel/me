<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les documents commerciaux : devis (ADEV-DEV), factures proforma (ADEV-PRO) et factures
     * (ADEV-FAC). Un brouillon n'a pas de numéro ; il en reçoit un, continu par type et par
     * année, quand il est émis, et ses lignes ne changent plus.
     */
    public function up(): void
    {
        Schema::create('commercial_documents', function (Blueprint $table) {
            $table->id();
            // quote, proforma ou invoice.
            $table->string('type');
            // draft, issued, accepted, declined, paid ou cancelled.
            $table->string('status')->default('draft');
            $table->string('number')->nullable()->unique();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedInteger('sequence')->nullable();
            $table->foreignId('engagement_id')->nullable()->constrained()->nullOnDelete();
            // Le document dont celui-ci découle (la proforma ou la facture d'un devis).
            $table->foreignId('source_id')->nullable()->constrained('commercial_documents')->nullOnDelete();
            $table->string('locale', 2)->default('fr');
            $table->string('client_name');
            $table->string('client_email')->nullable();
            $table->string('client_company')->nullable();
            $table->text('client_address')->nullable();
            $table->string('title');
            $table->string('currency', 3);
            // [{label, description, quantity, unit, unit_price}] ; montants en unités de la devise.
            $table->json('lines');
            $table->unsignedInteger('total');
            $table->unsignedTinyInteger('deposit_percent')->default(0);
            $table->text('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->date('issued_at')->nullable();
            // Devis : fin de validité ; facture : échéance de paiement.
            $table->date('valid_until')->nullable();
            $table->date('due_at')->nullable();
            // Lien privé du client pour consulter et accepter un devis.
            $table->string('token', 64)->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['type', 'year', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_documents');
    }
};

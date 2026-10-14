<?php

namespace App\Services;

use App\Ai\Agents\CommercialDocumentMailWriter;
use App\Enums\CommercialDocumentStatus;
use App\Enums\CommercialDocumentType;
use App\Enums\EngagementOutcome;
use App\Mail\CommercialDocumentMail;
use App\Models\CommercialDocument;
use App\Models\Engagement;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Services\Ai\TextAssistService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Le cycle des devis, proformas et factures : brouillon prérempli depuis une demande, émission
 * (numéro continu par type et par année, puis document figé), PDF, envoi au client avec un
 * message (rédigé par l'IA, relu par le développeur), conversion d'un devis en proforma ou en
 * facture, et issue (accepté, refusé, payé) reportée sur la demande liée.
 */
class CommercialDocuments
{
    public function __construct(private readonly TextAssistService $assist) {}

    /**
     * Un brouillon prérempli depuis une demande : le client, et les lignes de l'estimation (heures
     * marge comprise × taux de chaque élément) ou une ligne au haut de sa fourchette.
     */
    public function draftFromEngagement(Engagement $engagement, CommercialDocumentType $type = CommercialDocumentType::Quote): CommercialDocument
    {
        $estimate = (array) $engagement->estimate;
        $lines = collect($estimate['lines'] ?? [])
            ->map(fn (array $line): array => ['label' => $line['label'], 'description' => null, 'quantity' => $line['hours_max'], 'unit' => 'hour', 'unit_price' => $line['rate']])
            ->all();

        if ($lines === []) {
            $lines = [[
                'label' => $engagement->subject ?? ($engagement->locale === 'en' ? 'Project' : 'Projet'),
                'description' => null,
                'quantity' => 1,
                'unit' => 'flat',
                'unit_price' => (int) ($estimate['price_max'] ?? $engagement->budget_amount ?? 0),
            ]];
        }

        return $this->draft($type, [
            'engagement_id' => $engagement->id,
            'locale' => $engagement->locale === 'en' ? 'en' : 'fr',
            'client_name' => $engagement->name,
            'client_email' => $engagement->email,
            'client_company' => $engagement->company,
            'title' => $engagement->subject ?? ($engagement->locale === 'en' ? 'Project' : 'Projet'),
            'currency' => $estimate['currency'] ?? $engagement->budget_currency ?? 'CHF',
            'lines' => $lines,
        ]);
    }

    /**
     * Un brouillon avec les valeurs par défaut des réglages (acompte, conditions, validité ou
     * échéance), complétées par $attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function draft(CommercialDocumentType $type, array $attributes): CommercialDocument
    {
        $settings = SiteSetting::current();

        return CommercialDocument::query()->create([
            'type' => $type,
            'status' => CommercialDocumentStatus::Draft,
            'deposit_percent' => $type === CommercialDocumentType::Invoice ? 0 : (int) $settings->billing_deposit_percent,
            'payment_terms' => $settings->billing_payment_terms,
            'valid_until' => $type === CommercialDocumentType::Quote ? now()->addDays((int) $settings->billing_quote_validity_days) : null,
            'due_at' => $type === CommercialDocumentType::Quote ? null : now()->addDays((int) $settings->billing_invoice_due_days),
            ...$attributes,
        ]);
    }

    /**
     * Émet un brouillon : il reçoit le numéro suivant de son type pour l'année (ADEV-DEV-2026-001)
     * et ne se modifie plus.
     *
     * @throws ValidationException
     */
    public function issue(CommercialDocument $document): CommercialDocument
    {
        if (! $document->isDraft()) {
            return $document;
        }

        if ($document->total <= 0) {
            throw ValidationException::withMessages(['lines' => 'Le document doit avoir un montant avant d\'être émis.']);
        }

        return DB::transaction(function () use ($document): CommercialDocument {
            $year = (int) now()->format('Y');
            $sequence = (int) CommercialDocument::withTrashed()
                ->where('type', $document->type)
                ->where('year', $year)
                ->lockForUpdate()
                ->max('sequence') + 1;

            $document->forceFill([
                'status' => CommercialDocumentStatus::Issued,
                'year' => $year,
                'sequence' => $sequence,
                'number' => sprintf('%s-%d-%03d', $document->type->prefix(), $year, $sequence),
                'issued_at' => now(),
            ])->save();

            return $document;
        });
    }

    /** Une proforma ou une facture en brouillon, reprise d'un autre document (souvent un devis accepté). */
    public function convert(CommercialDocument $source, CommercialDocumentType $type): CommercialDocument
    {
        return $this->draft($type, [
            ...$source->only(['engagement_id', 'locale', 'client_name', 'client_email', 'client_company', 'client_address', 'title', 'currency', 'lines', 'notes']),
            'source_id' => $source->id,
        ]);
    }

    /** Le PDF du document, avec l'émetteur des réglages. */
    public function pdf(CommercialDocument $document): string
    {
        $settings = SiteSetting::current();
        $profile = Profile::query()->first();

        return Pdf::loadView('documents.commercial', [
            'document' => $document,
            'issuer' => [
                'name' => $settings->billing_business_name ?: $profile?->name,
                'legal_form' => $settings->billing_legal_form,
                'address' => $settings->billing_address,
                'registration_number' => $settings->billing_registration_number,
                'email' => $profile?->email,
                'phone' => $profile?->phone,
                'iban' => $settings->billing_iban,
                'bank' => $settings->billing_bank,
            ],
            'vatMention' => $settings->billing_vat_mention ?: ($document->locale === 'en' ? 'VAT not applicable' : 'TVA non applicable'),
            'acceptUrl' => $document->type === CommercialDocumentType::Quote ? $this->publicUrl($document) : null,
        ])->setPaper('a4')->output();
    }

    /** Le lien privé du client vers le document. */
    public function publicUrl(CommercialDocument $document): string
    {
        return route('commercial-documents.show', ['locale' => $document->locale, 'token' => $document->token]);
    }

    /**
     * L'objet et le message proposés par l'IA pour l'envoi ; null si elle est indisponible.
     *
     * @return array{subject: string, message: string}|null
     */
    public function suggestMail(CommercialDocument $document): ?array
    {
        $isQuote = $document->type === CommercialDocumentType::Quote;
        $prompt = implode("\n", array_filter([
            'Document : '.$document->type->label($document->locale).($document->number ? " {$document->number}" : ''),
            "Client : {$document->client_name}".($document->client_company ? " ({$document->client_company})" : ''),
            "Projet : {$document->title}",
            'Montant : '.number_format($document->total, 0, ',', ' ')." {$document->currency}",
            $document->deposit_percent > 0 ? "Acompte : {$document->deposit_percent} %" : null,
            $isQuote && $document->valid_until ? 'Valable jusqu\'au '.$document->valid_until->format('d.m.Y') : null,
            ! $isQuote && $document->due_at ? 'À régler avant le '.$document->due_at->format('d.m.Y') : null,
            'Lignes : '.collect($document->lines)->pluck('label')->implode(', '),
        ]));

        $result = $this->assist->promptJson(new CommercialDocumentMailWriter($document->locale), $prompt, function (array $data): ?array {
            $subject = trim((string) ($data['subject'] ?? ''));
            $message = trim((string) ($data['message'] ?? ''));

            return $subject === '' || $message === '' ? null : ['subject' => Str::limit($subject, 150, ''), 'message' => $message];
        }, 'ai.text_assist');

        /** @var array{subject: string, message: string}|null $result */
        return $result;
    }

    /**
     * Émet le document s'il est en brouillon, puis l'envoie au client avec le PDF joint.
     *
     * @throws ValidationException
     */
    public function send(CommercialDocument $document, string $subject, string $message): CommercialDocument
    {
        if (blank($document->client_email)) {
            throw ValidationException::withMessages(['client_email' => 'Le client n\'a pas d\'adresse email.']);
        }

        $document = $this->issue($document);

        Mail::to($document->client_email, $document->client_name)->send(new CommercialDocumentMail(
            $document,
            $subject,
            $message,
            $this->pdf($document),
            $document->type === CommercialDocumentType::Quote ? $this->publicUrl($document) : null,
        ));

        $document->forceFill(['sent_at' => now()])->save();

        return $document;
    }

    /** Le client accepte le devis : la demande liée est signée pour son montant. */
    public function accept(CommercialDocument $document): void
    {
        $document->forceFill(['status' => CommercialDocumentStatus::Accepted, 'accepted_at' => now()])->save();
        $document->engagement?->recordOutcome(EngagementOutcome::Won, $document->total, $document->currency);
    }

    /** Le client refuse le devis : la demande liée est perdue. */
    public function decline(CommercialDocument $document): void
    {
        $document->forceFill(['status' => CommercialDocumentStatus::Declined, 'declined_at' => now()])->save();
        $document->engagement?->recordOutcome(EngagementOutcome::Lost);
    }

    public function markPaid(CommercialDocument $document): void
    {
        $document->forceFill(['status' => CommercialDocumentStatus::Paid, 'paid_at' => now()])->save();
    }

    public function cancel(CommercialDocument $document): void
    {
        $document->forceFill(['status' => CommercialDocumentStatus::Cancelled])->save();
    }
}

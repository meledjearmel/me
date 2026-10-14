<?php

namespace App\Services;

use App\Ai\Agents\CommercialDocumentMailWriter;
use App\Enums\CommercialDocumentStatus;
use App\Enums\CommercialDocumentType;
use App\Enums\EngagementOutcome;
use App\Mail\CommercialDocumentMail;
use App\Models\CommercialDocument;
use App\Models\DocumentTemplate;
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

    /**
     * Le PDF du document, avec l'émetteur des réglages et le design de son type. L'aperçu de
     * l'éditeur de modèle passe un modèle non enregistré et, le cas échéant, le logo choisi.
     */
    public function pdf(CommercialDocument $document, ?DocumentTemplate $template = null, ?string $logoDataUri = null): string
    {
        $settings = SiteSetting::current();
        $profile = Profile::query()->first();
        $template ??= DocumentTemplate::for($document->type);

        return Pdf::loadView('documents.commercial', [
            'document' => $document,
            'template' => $template,
            'logo' => $template->show_logo ? ($logoDataUri ?? $template->logoDataUri()) : null,
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
        ])
            // Seuls les caractères utilisés de la police sont embarqués : un PDF léger à envoyer.
            ->setOption('isFontSubsettingEnabled', true)
            ->setPaper('a4')
            ->output();
    }

    /**
     * Un document d'exemple, non enregistré, pour prévisualiser le design d'un type.
     */
    public function sample(CommercialDocumentType $type, string $locale = 'fr'): CommercialDocument
    {
        $en = $locale === 'en';
        $document = new CommercialDocument([
            'type' => $type,
            'status' => CommercialDocumentStatus::Issued,
            'locale' => $locale,
            'client_name' => 'Awa Koné',
            'client_company' => 'Atelier Koné',
            'client_email' => 'awa@example.com',
            'client_address' => "Rue du Lac 12\n1003 Lausanne",
            'title' => $en ? 'Online shop' : 'Boutique en ligne',
            'currency' => 'CHF',
            'lines' => [
                ['label' => $en ? 'Online shop' : 'Boutique en ligne', 'description' => $en ? 'Catalogue, cart and customer account' : 'Catalogue, panier et compte client', 'quantity' => 48, 'unit' => 'hour', 'unit_price' => 100],
                ['label' => $en ? 'Online payment' : 'Paiement en ligne', 'description' => null, 'quantity' => 18, 'unit' => 'hour', 'unit_price' => 120],
                ['label' => $en ? 'Hosting, first year' : 'Hébergement, première année', 'description' => null, 'quantity' => 1, 'unit' => 'flat', 'unit_price' => 300],
            ],
            'deposit_percent' => $type === CommercialDocumentType::Invoice ? 0 : (int) SiteSetting::current()->billing_deposit_percent,
            'payment_terms' => SiteSetting::current()->billing_payment_terms,
            'notes' => $en ? 'Includes two rounds of revisions.' : 'Comprend deux séries de retouches.',
            'valid_until' => $type === CommercialDocumentType::Quote ? now()->addDays(30) : null,
            'due_at' => $type === CommercialDocumentType::Quote ? null : now()->addDays(30),
        ]);
        $document->total = CommercialDocument::totalOf($document->lines);
        $document->forceFill([
            'number' => sprintf('%s-%d-001', $type->prefix(), now()->year),
            'issued_at' => now(),
            'token' => 'exemple',
        ]);

        return $document;
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
    public function suggestMail(CommercialDocument $document, bool $reminder = false): ?array
    {
        $isQuote = $document->type === CommercialDocumentType::Quote;
        $prompt = implode("\n", array_filter([
            $reminder ? 'Type d\'email : relance n° '.($document->reminders_count + 1).', document envoyé le '.$document->sent_at?->format('d.m.Y')
                .($isQuote ? ', toujours sans réponse' : ', échéance dépassée de '.(int) $document->due_at?->diffInDays(now()).' jour(s)') : 'Type d\'email : premier envoi',
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

    /**
     * Signale les documents à relancer : un devis envoyé, toujours valable et sans réponse depuis
     * le délai des réglages ; une proforma ou une facture dont l'échéance est dépassée de ce délai.
     * Entre deux relances, le même délai s'écoule ; au-delà du maximum, plus rien n'est signalé.
     *
     * @return list<CommercialDocument> les documents nouvellement signalés
     */
    public function flagReminders(): array
    {
        $settings = SiteSetting::current();
        $max = (int) $settings->billing_max_reminders;

        if ($max === 0) {
            return [];
        }

        $quoteDays = max(1, (int) $settings->billing_quote_reminder_days);
        $invoiceDays = max(0, (int) $settings->billing_invoice_reminder_days);
        $waited = fn ($query, int $days) => $query->where(fn ($inner) => $inner
            ->where('last_reminded_at', '<=', now()->subDays(max(1, $days)))
            ->orWhere(fn ($first) => $first->whereNull('last_reminded_at')->where('sent_at', '<=', now()->subDays($days))));

        $quotes = CommercialDocument::query()
            ->where('type', CommercialDocumentType::Quote)
            ->where('status', CommercialDocumentStatus::Issued)
            ->whereNotNull('sent_at')
            ->where(fn ($query) => $query->whereNull('valid_until')->orWhere('valid_until', '>=', now()->toDateString()))
            ->tap(fn ($query) => $waited($query, $quoteDays));

        $invoices = CommercialDocument::query()
            ->whereIn('type', [CommercialDocumentType::Proforma, CommercialDocumentType::Invoice])
            ->where('status', CommercialDocumentStatus::Issued)
            ->where('due_at', '<=', now()->subDays($invoiceDays)->toDateString())
            ->where(fn ($query) => $query->whereNull('last_reminded_at')->orWhere('last_reminded_at', '<=', now()->subDays(max(1, $invoiceDays))));

        $flagged = [];

        foreach ([$quotes, $invoices] as $query) {
            foreach ($query->whereNull('reminder_due_at')->where('reminders_count', '<', $max)->get() as $document) {
                $document->forceFill(['reminder_due_at' => now()])->save();
                $flagged[] = $document;
            }
        }

        return $flagged;
    }

    /** Relance le client par email (PDF joint, lien d'acceptation pour un devis). */
    public function remind(CommercialDocument $document, string $subject, string $message): CommercialDocument
    {
        if (blank($document->client_email)) {
            throw ValidationException::withMessages(['client_email' => 'Le client n\'a pas d\'adresse email.']);
        }

        Mail::to($document->client_email, $document->client_name)->send(new CommercialDocumentMail(
            $document,
            $subject,
            $message,
            $this->pdf($document),
            $document->type === CommercialDocumentType::Quote ? $this->publicUrl($document) : null,
        ));

        $document->forceFill([
            'reminders_count' => $document->reminders_count + 1,
            'last_reminded_at' => now(),
            'reminder_due_at' => null,
        ])->save();

        return $document;
    }

    /** Retire la relance suggérée sans l'envoyer (elle pourra revenir après le délai). */
    public function dismissReminder(CommercialDocument $document): void
    {
        $document->forceFill(['reminder_due_at' => null, 'last_reminded_at' => now()])->save();
    }

    /** Le client accepte le devis : la demande liée est signée pour son montant. */
    public function accept(CommercialDocument $document): void
    {
        $document->forceFill(['status' => CommercialDocumentStatus::Accepted, 'accepted_at' => now(), 'reminder_due_at' => null])->save();
        $document->engagement?->recordOutcome(EngagementOutcome::Won, $document->total, $document->currency);
    }

    /** Le client refuse le devis : la demande liée est perdue. */
    public function decline(CommercialDocument $document): void
    {
        $document->forceFill(['status' => CommercialDocumentStatus::Declined, 'declined_at' => now(), 'reminder_due_at' => null])->save();
        $document->engagement?->recordOutcome(EngagementOutcome::Lost);
    }

    public function markPaid(CommercialDocument $document): void
    {
        $document->forceFill(['status' => CommercialDocumentStatus::Paid, 'paid_at' => now(), 'reminder_due_at' => null])->save();
    }

    public function cancel(CommercialDocument $document): void
    {
        $document->forceFill(['status' => CommercialDocumentStatus::Cancelled, 'reminder_due_at' => null])->save();
    }
}

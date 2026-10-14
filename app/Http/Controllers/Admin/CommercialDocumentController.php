<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Enums\CommercialDocumentStatus;
use App\Enums\CommercialDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CommercialDocumentRequest;
use App\Models\CommercialDocument;
use App\Models\Engagement;
use App\Models\SiteSetting;
use App\Services\CommercialDocuments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Devis, proformas et factures : brouillon (prérempli depuis une demande), émission numérotée,
 * aperçu et PDF, envoi au client avec un message proposé par l'IA, conversion et issue.
 */
class CommercialDocumentController extends Controller
{
    use PaginatesAdminLists;

    public function __construct(private readonly CommercialDocuments $documents) {}

    public function index(Request $request): Response
    {
        return Inertia::render('admin/commercial-documents/index', [
            'documents' => $this->paginateList(
                CommercialDocument::query()->when($request->boolean('reminder'), fn ($query) => $query->whereNotNull('reminder_due_at'))->latest(),
                $request,
                ['number', 'client_name', 'client_company', 'title'],
                ['type', 'status'],
            ),
            'filters' => $this->listFilters($request, ['type', 'status']),
        ]);
    }

    public function create(Request $request): Response
    {
        $settings = SiteSetting::current();
        $type = CommercialDocumentType::tryFrom((string) $request->query('type')) ?? CommercialDocumentType::Quote;

        return Inertia::render('admin/commercial-documents/create', [
            'type' => $type->value,
            'defaults' => [
                'deposit_percent' => $type === CommercialDocumentType::Invoice ? 0 : (int) $settings->billing_deposit_percent,
                'payment_terms' => $settings->billing_payment_terms,
                'valid_until' => $type === CommercialDocumentType::Quote ? now()->addDays((int) $settings->billing_quote_validity_days)->toDateString() : null,
                'due_at' => $type === CommercialDocumentType::Quote ? null : now()->addDays((int) $settings->billing_invoice_due_days)->toDateString(),
            ],
        ]);
    }

    public function store(CommercialDocumentRequest $request): RedirectResponse
    {
        $document = $this->documents->draft(CommercialDocumentType::from($request->validated('type')), $request->documentData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Brouillon créé.')]);

        return to_route('admin.commercial-documents.show', $document);
    }

    /** Un brouillon prérempli depuis une demande (son client et les lignes de son estimation). */
    public function fromEngagement(Request $request, Engagement $engagement): RedirectResponse
    {
        $type = CommercialDocumentType::tryFrom((string) $request->input('type')) ?? CommercialDocumentType::Quote;
        $document = $this->documents->draftFromEngagement($engagement, $type);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Brouillon prérempli depuis la demande, à relire.')]);

        return to_route('admin.commercial-documents.edit', $document);
    }

    public function show(CommercialDocument $commercialDocument): Response
    {
        return Inertia::render('admin/commercial-documents/show', [
            'document' => [
                ...$commercialDocument->load(['engagement:id,name,subject', 'source:id,number,type'])->toArray(),
                'deposit_amount' => $commercialDocument->depositAmount(),
                'public_url' => $commercialDocument->type === CommercialDocumentType::Quote ? $this->documents->publicUrl($commercialDocument) : null,
            ],
            'derived' => CommercialDocument::query()->where('source_id', $commercialDocument->id)->get(['id', 'type', 'number', 'status']),
        ]);
    }

    public function edit(CommercialDocument $commercialDocument): Response|RedirectResponse
    {
        if (! $commercialDocument->isDraft()) {
            return to_route('admin.commercial-documents.show', $commercialDocument);
        }

        return Inertia::render('admin/commercial-documents/edit', [
            'document' => $commercialDocument,
        ]);
    }

    public function update(CommercialDocumentRequest $request, CommercialDocument $commercialDocument): RedirectResponse
    {
        abort_unless($commercialDocument->isDraft(), 403, 'Un document émis ne se modifie plus.');

        $commercialDocument->update($request->documentData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Brouillon enregistré.')]);

        return to_route('admin.commercial-documents.show', $commercialDocument);
    }

    /** Le PDF, affiché dans le navigateur (ou téléchargé avec ?download=1). */
    public function pdf(Request $request, CommercialDocument $commercialDocument): HttpResponse
    {
        return response($this->documents->pdf($commercialDocument), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline').'; filename="'.$commercialDocument->filename().'"',
        ]);
    }

    /** Émet le document sans l'envoyer : il reçoit son numéro et se fige. */
    public function issue(CommercialDocument $commercialDocument): RedirectResponse
    {
        $this->documents->issue($commercialDocument);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document émis : :number.', ['number' => $commercialDocument->number])]);

        return back();
    }

    /** L'objet et le message proposés par l'IA pour l'envoi. */
    public function suggestMail(Request $request, CommercialDocument $commercialDocument): JsonResponse
    {
        $mail = $this->documents->suggestMail($commercialDocument, $request->boolean('reminder'));

        return $mail === null
            ? response()->json(['message' => __('L\'assistance IA est momentanément indisponible.')], 503)
            : response()->json($mail);
    }

    /** Envoie le document au client (et l'émet s'il est en brouillon). */
    public function send(Request $request, CommercialDocument $commercialDocument): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $this->documents->send($commercialDocument, $data['subject'], $data['message']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document envoyé à :email.', ['email' => $commercialDocument->client_email])]);

        return back();
    }

    /** Relance le client (devis sans réponse, facture en retard) avec le message relu. */
    public function remind(Request $request, CommercialDocument $commercialDocument): RedirectResponse
    {
        abort_if($commercialDocument->isDraft(), 422, 'Envoyez le document d\'abord.');

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $this->documents->remind($commercialDocument, $data['subject'], $data['message']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Relance envoyée à :email.', ['email' => $commercialDocument->client_email])]);

        return back();
    }

    /** Écarte la relance suggérée sans rien envoyer. */
    public function dismissReminder(CommercialDocument $commercialDocument): RedirectResponse
    {
        $this->documents->dismissReminder($commercialDocument);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Relance écartée.')]);

        return back();
    }

    /** Une proforma ou une facture en brouillon, reprise de ce document. */
    public function convert(Request $request, CommercialDocument $commercialDocument): RedirectResponse
    {
        $type = CommercialDocumentType::from($request->validate([
            'type' => ['required', Rule::in([CommercialDocumentType::Proforma->value, CommercialDocumentType::Invoice->value])],
        ])['type']);

        $document = $this->documents->convert($commercialDocument, $type);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Brouillon créé à partir de :number.', ['number' => $commercialDocument->number ?? 'ce document'])]);

        return to_route('admin.commercial-documents.edit', $document);
    }

    /** Accepté ou refusé (devis), payé (proforma, facture) ou annulé : l'issue suit sur la demande. */
    public function status(Request $request, CommercialDocument $commercialDocument): RedirectResponse
    {
        $status = CommercialDocumentStatus::from($request->validate([
            'status' => ['required', Rule::in(['accepted', 'declined', 'paid', 'cancelled'])],
        ])['status']);

        abort_if($commercialDocument->isDraft(), 422, 'Émettez le document d\'abord.');

        match ($status) {
            CommercialDocumentStatus::Accepted => $this->documents->accept($commercialDocument),
            CommercialDocumentStatus::Declined => $this->documents->decline($commercialDocument),
            CommercialDocumentStatus::Paid => $this->documents->markPaid($commercialDocument),
            default => $this->documents->cancel($commercialDocument),
        };

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document mis à jour.')]);

        return back();
    }

    /** Seul un brouillon se supprime ; un document émis s'annule (sa numérotation reste continue). */
    public function destroy(CommercialDocument $commercialDocument): RedirectResponse
    {
        abort_unless($commercialDocument->isDraft(), 403, 'Un document émis s\'annule, il ne se supprime pas.');

        $commercialDocument->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Brouillon supprimé.')]);

        return to_route('admin.commercial-documents.index');
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\CommercialDocumentStatus;
use App\Enums\CommercialDocumentType;
use App\Jobs\SendPushNotification;
use App\Models\CommercialDocument;
use App\Services\CommercialDocuments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Le lien privé d'un document pour le client : il le consulte, télécharge le PDF et, pour un
 * devis encore valable, l'accepte ou le refuse. Un brouillon ou un document annulé reste invisible.
 */
class CommercialDocumentController extends Controller
{
    public function __construct(private readonly CommercialDocuments $documents) {}

    public function show(Request $request, string $locale, string $token): Response
    {
        $document = $this->find($token);

        return Inertia::render('public/commercial-document', [
            'document' => [
                'type' => $document->type->value,
                'type_label' => $document->type->label($document->locale),
                'number' => $document->number,
                'status' => $document->status->value,
                'title' => $document->title,
                'client_name' => $document->client_name,
                'currency' => $document->currency,
                'total' => $document->total,
                'deposit_percent' => $document->deposit_percent,
                'deposit_amount' => $document->depositAmount(),
                'lines' => $document->lines,
                'issued_at' => $document->issued_at?->toDateString(),
                'valid_until' => $document->valid_until?->toDateString(),
                'due_at' => $document->due_at?->toDateString(),
                'can_answer' => $this->canAnswer($document),
            ],
            'token' => $token,
        ])->toResponse($request)->withHeaders(['X-Robots-Tag' => 'noindex, nofollow']);
    }

    public function pdf(string $locale, string $token): HttpResponse
    {
        $document = $this->find($token);

        return response($this->documents->pdf($document), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$document->filename().'"',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    public function accept(string $locale, string $token): RedirectResponse
    {
        $document = $this->find($token);
        abort_unless($this->canAnswer($document), 409);

        $this->documents->accept($document);
        SendPushNotification::dispatch('Devis accepté', "{$document->number} · {$document->client_name}", ['type' => 'commercial_document', 'id' => (string) $document->id]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Merci ! Votre accord est bien enregistré, je reviens vers vous très vite.')]);

        return back();
    }

    public function decline(string $locale, string $token): RedirectResponse
    {
        $document = $this->find($token);
        abort_unless($this->canAnswer($document), 409);

        $this->documents->decline($document);
        SendPushNotification::dispatch('Devis refusé', "{$document->number} · {$document->client_name}", ['type' => 'commercial_document', 'id' => (string) $document->id]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('C\'est noté, merci pour votre réponse.')]);

        return back();
    }

    private function find(string $token): CommercialDocument
    {
        return CommercialDocument::query()
            ->where('token', $token)
            ->whereNotIn('status', [CommercialDocumentStatus::Draft, CommercialDocumentStatus::Cancelled])
            ->firstOrFail();
    }

    /** Un devis émis, pas encore tranché et toujours valable. */
    private function canAnswer(CommercialDocument $document): bool
    {
        return $document->type === CommercialDocumentType::Quote
            && $document->status === CommercialDocumentStatus::Issued
            && ($document->valid_until === null || $document->valid_until->endOfDay()->isFuture());
    }
}

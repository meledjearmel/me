<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Enums\CommercialDocumentStatus;
use App\Enums\CommercialDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CommercialDocumentRequest;
use App\Http\Resources\Api\V1\CommercialDocumentResource;
use App\Models\CommercialDocument;
use App\Models\Engagement;
use App\Services\CommercialDocuments;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * @tags Devis et factures
 */
class CommercialDocumentController extends Controller
{
    use PaginatesAdminLists;

    public function __construct(private readonly CommercialDocuments $documents) {}

    /**
     * Liste des devis et factures
     *
     * Du plus récent au plus ancien.
     */
    #[QueryParameter('search', description: 'Recherche dans le numéro, le client et le projet.', type: 'string')]
    #[QueryParameter('type', description: '`quote`, `proforma` ou `invoice`.', type: 'string')]
    #[QueryParameter('status', description: '`draft`, `issued`, `accepted`, `declined`, `paid` ou `cancelled`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return CommercialDocumentResource::collection(
            $this->paginateList(CommercialDocument::query()->latest(), $request, ['number', 'client_name', 'client_company', 'title'], ['type', 'status'])
        );
    }

    /**
     * Créer un brouillon
     *
     * Les valeurs absentes (acompte, conditions, validité ou échéance) viennent des réglages de
     * facturation. Le brouillon n'a pas de numéro tant qu'il n'est pas émis.
     */
    public function store(CommercialDocumentRequest $request): CommercialDocumentResource
    {
        return new CommercialDocumentResource($this->documents->draft(CommercialDocumentType::from($request->validated('type')), $request->documentData()));
    }

    /**
     * Créer un brouillon depuis une demande
     *
     * Reprend le client de la demande et les lignes de son estimation (heures, marge comprise,
     * au taux de chaque élément), ou une ligne au haut de sa fourchette.
     */
    public function fromEngagement(Request $request, Engagement $engagement): CommercialDocumentResource
    {
        $type = $request->validate(['type' => ['nullable', Rule::enum(CommercialDocumentType::class)]])['type'] ?? null;

        return new CommercialDocumentResource($this->documents->draftFromEngagement($engagement, CommercialDocumentType::tryFrom((string) $type) ?? CommercialDocumentType::Quote));
    }

    /**
     * Détail d'un document
     */
    public function show(CommercialDocument $commercialDocument): CommercialDocumentResource
    {
        return new CommercialDocumentResource($commercialDocument);
    }

    /**
     * Modifier un brouillon
     *
     * Un document émis ne se modifie plus (409).
     */
    public function update(CommercialDocumentRequest $request, CommercialDocument $commercialDocument): CommercialDocumentResource
    {
        abort_unless($commercialDocument->isDraft(), 409, 'Un document émis ne se modifie plus.');

        $commercialDocument->update($request->documentData());

        return new CommercialDocumentResource($commercialDocument);
    }

    /**
     * Le PDF d'un document
     */
    public function pdf(CommercialDocument $commercialDocument): Response
    {
        return response($this->documents->pdf($commercialDocument), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$commercialDocument->filename().'"',
        ]);
    }

    /**
     * Émettre un brouillon
     *
     * Il reçoit le numéro suivant de son type pour l'année (`ADEV-DEV-2026-001`) et se fige.
     */
    public function issue(CommercialDocument $commercialDocument): CommercialDocumentResource
    {
        return new CommercialDocumentResource($this->documents->issue($commercialDocument));
    }

    /**
     * Proposer l'email d'envoi avec l'IA
     *
     * L'objet et le message, à relire avant `send`. 503 si l'IA est indisponible.
     *
     * @response array{subject: string, message: string}
     */
    public function suggestMail(CommercialDocument $commercialDocument): JsonResponse
    {
        $mail = $this->documents->suggestMail($commercialDocument);

        return $mail === null
            ? response()->json(['message' => 'L\'assistance IA est momentanément indisponible.'], 503)
            : response()->json($mail);
    }

    /**
     * Envoyer au client
     *
     * Émet le document s'il est en brouillon, puis l'envoie par email avec le PDF joint et, pour
     * un devis, le lien pour l'accepter en ligne.
     */
    public function send(Request $request, CommercialDocument $commercialDocument): CommercialDocumentResource
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        return new CommercialDocumentResource($this->documents->send($commercialDocument, $data['subject'], $data['message']));
    }

    /**
     * Convertir en proforma ou en facture
     *
     * Crée un brouillon qui reprend le client, le projet et les lignes du document.
     */
    public function convert(Request $request, CommercialDocument $commercialDocument): CommercialDocumentResource
    {
        $type = $request->validate([
            'type' => ['required', Rule::in([CommercialDocumentType::Proforma->value, CommercialDocumentType::Invoice->value])],
        ])['type'];

        return new CommercialDocumentResource($this->documents->convert($commercialDocument, CommercialDocumentType::from($type)));
    }

    /**
     * Changer le statut d'un document émis
     *
     * `accepted` ou `declined` (devis : la demande liée est signée pour le total, ou perdue),
     * `paid` (proforma, facture) ou `cancelled`.
     */
    public function status(Request $request, CommercialDocument $commercialDocument): CommercialDocumentResource
    {
        $status = CommercialDocumentStatus::from($request->validate([
            'status' => ['required', Rule::in(['accepted', 'declined', 'paid', 'cancelled'])],
        ])['status']);

        abort_if($commercialDocument->isDraft(), 409, 'Émettez le document d\'abord.');

        match ($status) {
            CommercialDocumentStatus::Accepted => $this->documents->accept($commercialDocument),
            CommercialDocumentStatus::Declined => $this->documents->decline($commercialDocument),
            CommercialDocumentStatus::Paid => $this->documents->markPaid($commercialDocument),
            default => $this->documents->cancel($commercialDocument),
        };

        return new CommercialDocumentResource($commercialDocument->refresh());
    }

    /**
     * Supprimer un brouillon
     *
     * Un document émis s'annule (`status` = `cancelled`) : sa numérotation reste continue (409).
     */
    public function destroy(CommercialDocument $commercialDocument): Response
    {
        abort_unless($commercialDocument->isDraft(), 409, 'Un document émis s\'annule, il ne se supprime pas.');

        $commercialDocument->delete();

        return response()->noContent();
    }
}

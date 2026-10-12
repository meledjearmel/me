<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RevisionStatus;
use App\Http\Controllers\Controller;
use App\Models\ContentRevision;
use App\Services\Ai\ContentRevisions;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Révisions proposées
 */
class ContentRevisionController extends Controller
{
    public function __construct(private readonly ContentRevisions $revisions) {}

    /**
     * Lister les révisions
     *
     * Modifications de contenus publiés (projets, articles, expériences, formations,
     * certifications, page « Uses », compétences) ou de la page « Now » proposées par l'IA via le
     * serveur MCP. Rien ne change sur le site avant `apply`. Les 100 plus récentes du statut demandé.
     *
     * @response array{data: array<int, array{id: int, content_type: string, content_type_label: string, content_id: int|null, title: string, edit_url: string|null, summary: string, drafted_by: string|null, status: string, created_at: string|null, decided_at: string|null}>}
     */
    #[QueryParameter('status', description: 'Statut : `pending` (par défaut), `applied` ou `rejected`.', type: 'string', default: 'pending')]
    public function index(Request $request): JsonResponse
    {
        $status = RevisionStatus::tryFrom((string) $request->query('status')) ?? RevisionStatus::Pending;

        return response()->json([
            'data' => ContentRevision::query()->where('status', $status)->latest()->limit(100)->get()
                ->map(fn (ContentRevision $revision): array => $this->revisions->present($revision))
                ->values(),
        ]);
    }

    /**
     * Voir une révision
     *
     * Avec `fields` : chaque champ modifié (`field`, `locale` fr ou en), sa valeur au moment de
     * la proposition (`before`), la valeur proposée (`after`) et la valeur actuelle (`current`,
     * différente de `before` si le contenu a changé depuis).
     *
     * @response array{data: array{id: int, content_type: string, content_type_label: string, content_id: int|null, title: string, edit_url: string|null, summary: string, drafted_by: string|null, status: string, created_at: string|null, decided_at: string|null, fields: array<int, array{field: string, locale: string, before: mixed, after: mixed, current: mixed}>}}
     */
    public function show(ContentRevision $revision): JsonResponse
    {
        return response()->json(['data' => $this->revisions->present($revision, detailed: true)]);
    }

    /**
     * Appliquer une révision
     *
     * Met à jour le contenu sur le site (français et anglais). 409 si la révision n'est plus en attente.
     *
     * @response array{data: array{id: int, status: string}}
     */
    public function apply(ContentRevision $revision): JsonResponse
    {
        abort_unless($revision->status === RevisionStatus::Pending, 409);

        $this->revisions->apply($revision);

        return response()->json(['data' => $this->revisions->present($revision->refresh())]);
    }

    /**
     * Rejeter une révision
     *
     * Le contenu ne change pas. 409 si la révision n'est plus en attente.
     *
     * @response array{data: array{id: int, status: string}}
     */
    public function reject(ContentRevision $revision): JsonResponse
    {
        abort_unless($revision->status === RevisionStatus::Pending, 409);

        $this->revisions->reject($revision);

        return response()->json(['data' => $this->revisions->present($revision->refresh())]);
    }
}

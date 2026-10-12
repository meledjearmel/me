<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RevisionStatus;
use App\Http\Controllers\Controller;
use App\Models\ContentRevision;
use App\Services\Ai\ContentRevisions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Révisions de contenus publiés proposées par l'IA (serveur MCP) : avant/après, puis
 * application ou rejet. Rien ne change sur le site avant l'application.
 */
class ContentRevisionController extends Controller
{
    public function __construct(private readonly ContentRevisions $revisions) {}

    public function index(Request $request): Response
    {
        $status = RevisionStatus::tryFrom((string) $request->query('status')) ?? RevisionStatus::Pending;

        return Inertia::render('admin/revisions/index', [
            'revisions' => ContentRevision::query()->where('status', $status)->latest()->limit(100)->get()
                ->map(fn (ContentRevision $revision): array => $this->revisions->present($revision)),
            'status' => $status->value,
        ]);
    }

    public function show(ContentRevision $revision): Response
    {
        return Inertia::render('admin/revisions/show', [
            'revision' => $this->revisions->present($revision, detailed: true),
        ]);
    }

    public function apply(ContentRevision $revision): RedirectResponse
    {
        abort_unless($revision->status === RevisionStatus::Pending, 409);

        $this->revisions->apply($revision);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Révision appliquée : le contenu est à jour sur le site.')]);

        return to_route('admin.revisions.index');
    }

    public function reject(ContentRevision $revision): RedirectResponse
    {
        abort_unless($revision->status === RevisionStatus::Pending, 409);

        $this->revisions->reject($revision);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Révision rejetée.')]);

        return to_route('admin.revisions.index');
    }
}

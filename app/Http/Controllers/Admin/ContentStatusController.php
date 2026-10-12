<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ContentStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Change le statut d'un contenu depuis sa fiche (brouillon, publié, archivé…). */
class ContentStatusController extends Controller
{
    public function __invoke(Request $request, ContentStatus $statuses, string $type, int $id): RedirectResponse
    {
        $request->validate(['status' => ['required', 'string']]);

        $content = $statuses->find($type, $id);
        abort_if($content === null, 404);

        $statuses->change($type, $content, $request->input('status'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Statut mis à jour.')]);

        return back();
    }
}

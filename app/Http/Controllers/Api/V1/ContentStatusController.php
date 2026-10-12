<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ContentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Statut des contenus
 */
class ContentStatusController extends Controller
{
    /**
     * Changer le statut d'un contenu
     *
     * Change seulement le statut, sans renvoyer tout le formulaire. `type` : `project`
     * (`draft`, `published`, `archived`), `post`, `experience`, `education`, `certification`,
     * `uses_item` ou `skill` (`draft` ou `published`). Publier est refusé (422) tant que
     * l'anglais obligatoire manque ; un article publié sans date paraît tout de suite.
     *
     * @response array{data: array{type: string, id: int, status: string}}
     */
    public function __invoke(Request $request, ContentStatus $statuses, string $type, int $id): JsonResponse
    {
        $request->validate([
            /** Nouveau statut. */
            'status' => ['required', 'string'],
        ]);

        $content = $statuses->find($type, $id);
        abort_if($content === null, 404);

        $statuses->change($type, $content, $request->input('status'));

        return response()->json(['data' => ['type' => $type, 'id' => $id, 'status' => $content->status->value]]);
    }
}

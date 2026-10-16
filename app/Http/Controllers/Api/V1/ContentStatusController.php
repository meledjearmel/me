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
     * Change seulement le statut, sans renvoyer tout le formulaire (changement rapide depuis une
     * liste). `type` et statuts :
     *
     * - `project` : `draft`, `published`, `archived` ;
     * - `post`, `experience`, `education`, `certification`, `distinction`, `uses_item`, `skill`,
     *   `service`, `domain`, `job_profile` : `draft` ou `published` ;
     * - `contact` : `new`, `read`, `replied` ; `engagement` : `new`, `handled` ;
     * - `job_application` : `draft`, `active`, `archived` ;
     * - `comment`, `testimonial` : `pending`, `approved`, `rejected` ;
     * - `appointment` : `confirmed` ou `declined` depuis `pending`, `declined` depuis `confirmed`
     *   (email envoyé au visiteur, créneau libéré) ;
     * - `commercial_document` : `issued` depuis `draft` (numéro attribué), puis `accepted`,
     *   `declined`, `paid` ou `cancelled` ;
     * - `revision` : `applied` ou `rejected` depuis `pending` (appliquer modifie le contenu).
     *
     * Un changement impossible depuis le statut actuel est refusé (422). Publier est refusé tant
     * que l'anglais obligatoire manque ; un article publié sans date paraît tout de suite.
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

        return response()->json(['data' => ['type' => $type, 'id' => $id, 'status' => $content->refresh()->status->value]]);
    }
}

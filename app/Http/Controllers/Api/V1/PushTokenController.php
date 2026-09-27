<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PushTokenDestroyRequest;
use App\Http\Requests\Api\V1\PushTokenRequest;
use App\Models\PushToken;
use Illuminate\Http\Response;

/**
 * @tags Notifications push
 */
class PushTokenController extends Controller
{
    /**
     * Enregistrer un appareil
     *
     * À appeler après connexion (et à chaque démarrage, au cas où le jeton a changé) pour
     * recevoir les notifications push. Un jeton déjà connu est simplement réattribué à
     * l'utilisateur courant (réinstallation, nouveau compte sur le même appareil).
     */
    public function store(PushTokenRequest $request): Response
    {
        PushToken::query()->updateOrCreate(
            ['token' => $request->validated('token')],
            ['user_id' => $request->user()->id, 'platform' => $request->validated('platform')],
        );

        return response()->noContent();
    }

    /**
     * Retirer un appareil
     *
     * À appeler à la déconnexion pour que cet appareil arrête de recevoir des notifications.
     */
    public function destroy(PushTokenDestroyRequest $request): Response
    {
        PushToken::query()->where('token', $request->validated('token'))->delete();

        return response()->noContent();
    }
}

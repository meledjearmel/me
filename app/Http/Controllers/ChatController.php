<?php

namespace App\Http\Controllers;

use App\Ai\Agents\PortfolioAssistant;
use App\Http\Requests\ChatRequest;
use App\Services\PostMentions;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatController extends Controller
{
    public function store(ChatRequest $request, PostMentions $mentions): JsonResponse
    {
        $assistant = new PortfolioAssistant($request->history(), app()->getLocale());

        // La chaîne est parcourue ici plutôt que par le failover du package, qui ne
        // rebondit que sur quota, surcharge ou panne réseau : un modèle retiré (404),
        // un réglage de compte ou une réponse vide doivent aussi passer au suivant.
        foreach (config('ai.chat.providers') as $entry) {
            [$provider, $model] = array_pad(explode(':', $entry, 2), 2, null);

            try {
                $reply = $assistant->prompt($request->validated('message'), provider: $provider, model: $model)->text;
            } catch (Throwable $exception) {
                Log::warning("Assistant : échec de [{$entry}] : ".$exception->getMessage());

                continue;
            }

            if (filled($reply)) {
                return response()->json([
                    'reply' => $reply,
                    // Pages de projet ou d'article citées : le chat les montre en mentions avec leur carte.
                    'mentions' => (object) $mentions->cardsForUrls($reply, app()->getLocale()),
                ]);
            }

            Log::warning("Assistant : réponse vide de [{$entry}].");
        }

        return response()->json([
            'message' => __('L\'assistant est momentanément indisponible. Utilisez plutôt la page contact.'),
        ], 503);
    }
}

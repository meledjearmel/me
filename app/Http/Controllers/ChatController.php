<?php

namespace App\Http\Controllers;

use App\Ai\Agents\PortfolioAssistant;
use App\Http\Requests\ChatRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatController extends Controller
{
    public function store(ChatRequest $request): JsonResponse
    {
        $assistant = new PortfolioAssistant($request->history(), app()->getLocale());

        // La chaîne est parcourue ici plutôt que par le failover du package, qui ne
        // rebondit que sur quota, surcharge ou panne réseau : un modèle retiré (404),
        // un réglage de compte ou une réponse vide doivent aussi passer au suivant.
        foreach (config('ai.chat.providers') as $provider) {
            try {
                $reply = $assistant->prompt($request->validated('message'), provider: $provider)->text;
            } catch (Throwable $exception) {
                Log::warning("Assistant : échec de [{$provider}] : ".$exception->getMessage());

                continue;
            }

            if (filled($reply)) {
                return response()->json(['reply' => $reply]);
            }

            Log::warning("Assistant : réponse vide de [{$provider}].");
        }

        return response()->json([
            'message' => __('L\'assistant est momentanément indisponible. Utilisez plutôt la page contact.'),
        ], 503);
    }
}

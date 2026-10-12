<?php

namespace App\Mcp\Concerns;

use App\Mcp\AiClient;
use Laravel\Mcp\Request;

/** Le client IA qui appelle l'outil, pour marquer les brouillons qu'il rédige (voir AiClient). */
trait NamesAiClient
{
    private function aiClient(Request $request): string
    {
        return AiClient::name($request->user());
    }
}

<?php

namespace App\Mcp\Concerns;

use Laravel\Mcp\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Le client IA qui appelle le serveur MCP, d'après le nom de son jeton (« Claude Code »,
 * « Codex »…), pour marquer les brouillons qu'il rédige.
 */
trait NamesAiClient
{
    private function aiClient(Request $request): string
    {
        $token = $request->user()?->currentAccessToken();

        return $token instanceof PersonalAccessToken ? mb_substr($token->name, 0, 60) : 'IA';
    }
}

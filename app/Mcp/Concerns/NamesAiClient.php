<?php

namespace App\Mcp\Concerns;

use Laravel\Mcp\Request;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Passport;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Le client IA qui appelle le serveur MCP, pour marquer les brouillons qu'il rédige : le nom
 * du jeton Sanctum (« Claude Code », « Codex »…) ou celui sous lequel un connecteur OAuth
 * s'est enregistré (« Claude », « ChatGPT »…).
 */
trait NamesAiClient
{
    private function aiClient(Request $request): string
    {
        $token = $request->user()?->currentAccessToken();

        $name = match (true) {
            $token instanceof PersonalAccessToken => $token->name,
            $token instanceof AccessToken => Passport::client()->newQuery()->find($token->oauth_client_id)?->name,
            default => null,
        };

        return filled($name) ? mb_substr($name, 0, 60) : 'IA';
    }
}

<?php

namespace App\Mcp;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Passport;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Le client IA qui appelle le serveur MCP : le nom du jeton Sanctum (« Claude Code »,
 * « Codex »…) ou celui sous lequel un connecteur OAuth s'est enregistré (« Claude »,
 * « ChatGPT »…).
 */
class AiClient
{
    public static function name(?Authenticatable $user): string
    {
        $token = $user !== null && method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;

        $name = match (true) {
            $token instanceof PersonalAccessToken => $token->name,
            $token instanceof AccessToken => Passport::client()->newQuery()->find($token->oauth_client_id)?->name,
            default => null,
        };

        return filled($name) ? mb_substr($name, 0, 60) : 'IA';
    }
}

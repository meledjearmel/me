<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\Contracts\ScopeAuthorizable;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accès au serveur MCP : un jeton Sanctum avec la capacité « mcp » (Claude Code, Codex,
 * créé dans l'administration) ou un jeton OAuth Passport avec la portée « mcp:use »
 * (connecteurs Claude et ChatGPT).
 */
class EnsureMcpAccess
{
    public const string OAUTH_SCOPE = 'mcp:use';

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        $allowed = match (true) {
            $token instanceof PersonalAccessToken => $token->can(RejectMcpTokens::ABILITY),
            $token instanceof ScopeAuthorizable => $token->can(self::OAUTH_SCOPE),
            default => false,
        };

        abort_unless($allowed, Response::HTTP_FORBIDDEN, __('Ce jeton ne donne pas accès au serveur MCP.'));

        return $next($request);
    }
}

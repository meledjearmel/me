<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un jeton MCP (capacité « mcp » seule, voir McpTokenController) ne sert qu'au serveur
 * MCP : l'API de l'app mobile le refuse.
 */
class RejectMcpTokens
{
    public const string ABILITY = 'mcp';

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken && $token->abilities === [self::ABILITY]) {
            abort(Response::HTTP_FORBIDDEN, __('Ce jeton est réservé au serveur MCP.'));
        }

        return $next($request);
    }
}

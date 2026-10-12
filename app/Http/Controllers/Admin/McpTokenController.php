<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RejectMcpTokens;
use App\Http\Requests\Admin\McpTokenRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Accès au serveur MCP : jetons créés ici pour Claude Code et Codex (limités au MCP, refusés
 * par l'API mobile), et connecteurs OAuth autorisés (Claude, ChatGPT).
 */
class McpTokenController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('admin/mcp-server/access', [
            'tokens' => $this->mcpTokens($request)
                ->latest()
                ->get(['id', 'name', 'last_used_at', 'created_at']),
            'connectors' => $this->connectors($request),
            'serverUrl' => url('/mcp/portfolio'),
        ]);
    }

    public function store(McpTokenRequest $request): RedirectResponse
    {
        $token = $request->user()->createToken($request->validated('name'), [RejectMcpTokens::ABILITY]);

        // Le jeton en clair n'est affiché qu'une fois.
        Inertia::flash('mcpToken', $token->plainTextToken);

        return back();
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $this->mcpTokens($request)->whereKey($token)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Jeton révoqué.')]);

        return back();
    }

    /** Coupe l'accès d'un connecteur : ses jetons et leurs jetons de renouvellement sont révoqués. */
    public function destroyConnector(Request $request, string $client): RedirectResponse
    {
        $this->activeOAuthTokens($request)->where('client_id', $client)->get()
            ->each(function (Token $token): void {
                $token->revoke();
                $token->refreshToken?->revoke();
            });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Connecteur déconnecté.')]);

        return back();
    }

    /** @return Builder<PersonalAccessToken> */
    private function mcpTokens(Request $request): Builder
    {
        return PersonalAccessToken::query()
            ->whereMorphedTo('tokenable', $request->user())
            ->whereJsonContains('abilities', RejectMcpTokens::ABILITY);
    }

    /** @return Builder<Token> */
    private function activeOAuthTokens(Request $request): Builder
    {
        return Passport::token()->newQuery()
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('revoked', false);
    }

    /**
     * Connecteurs ayant un accès en cours (jeton non révoqué, éventuellement expiré mais
     * renouvelable), avec leur dernière connexion.
     *
     * @return Collection<int, array{id: string, name: string, last_connected_at: string|null}>
     */
    private function connectors(Request $request): Collection
    {
        return $this->activeOAuthTokens($request)->with('client')->latest()->get()
            ->groupBy('client_id')
            ->map(fn (Collection $tokens): array => [
                'id' => (string) $tokens->first()->client_id,
                'name' => $tokens->first()->client?->name ?? 'Client inconnu',
                'last_connected_at' => $tokens->first()->created_at?->toIso8601String(),
            ])
            ->values();
    }
}

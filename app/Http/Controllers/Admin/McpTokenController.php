<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RejectMcpTokens;
use App\Http\Requests\Admin\McpTokenRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Jetons du serveur MCP (Claude Code, Codex…) : limités au MCP, refusés par l'API mobile.
 */
class McpTokenController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('admin/mcp-tokens/index', [
            'tokens' => $this->mcpTokens($request)
                ->latest()
                ->get(['id', 'name', 'last_used_at', 'created_at']),
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

    /** @return Builder<PersonalAccessToken> */
    private function mcpTokens(Request $request): Builder
    {
        return PersonalAccessToken::query()
            ->whereMorphedTo('tokenable', $request->user())
            ->whereJsonContains('abilities', RejectMcpTokens::ABILITY);
    }
}

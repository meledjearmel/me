<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mcp\ActivityLog;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Serveur MCP
 */
class McpActivityController extends Controller
{
    /**
     * Activité du serveur MCP
     *
     * Les 200 derniers appels d'outils et de commandes du serveur MCP (90 jours conservés) :
     * client (nom du jeton ou du connecteur), outil (`name`, `title`), contenu visé (`target`,
     * avec son lien d'administration s'il existe encore), résultat (`success`, `error`) et
     * durée. Le contenu envoyé n'est pas conservé.
     *
     * @response array{data: array<int, array{id: int, client: string, kind: string, name: string, title: string, target: array{label: string, url: string|null}|null, success: bool, error: string|null, duration_ms: int, created_at: string}>}
     */
    #[QueryParameter('client', description: 'Ne garder que les appels de ce client (ex. `Claude`, `Claude Code`).', type: 'string')]
    public function index(Request $request, ActivityLog $log): JsonResponse
    {
        return response()->json(['data' => $log->latest(client: $request->query('client') ?: null)->values()]);
    }
}

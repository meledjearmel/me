<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Mcp\ActivityLog;
use App\Models\McpActivity;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Serveur MCP
 */
class McpActivityController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Activité du serveur MCP
     *
     * Les appels d'outils et de commandes du serveur MCP (90 jours conservés), du plus récent
     * au plus ancien, paginés : client (nom du jeton ou du connecteur), outil (`name`, `title`),
     * contenu visé (`target`, avec son lien d'administration s'il existe encore), résultat
     * (`success`, `error`) et durée. Le contenu envoyé n'est pas conservé. `filters` donne les
     * clients et les outils présents dans le journal, pour construire les filtres.
     *
     * @response array{data: array<int, array{id: int, client: string, kind: string, name: string, title: string, target: array{label: string, url: string|null}|null, success: bool, error: string|null, duration_ms: int, created_at: string}>, current_page: int, last_page: int, per_page: int, total: int, filters: array{clients: list<string>, tools: list<array{value: string, label: string}>}}
     */
    #[QueryParameter('search', description: 'Recherche dans l’outil (nom technique ou titre) et le message d’erreur.', type: 'string')]
    #[QueryParameter('client', description: 'Ne garder que les appels de ce client (ex. `Claude`, `Claude Code`).', type: 'string')]
    #[QueryParameter('name', description: 'Ne garder que cet outil ou cette commande (ex. `get-project`).', type: 'string')]
    #[QueryParameter('kind', description: 'Type d’appel : `tool` ou `prompt`.', type: 'string')]
    #[QueryParameter('status', description: 'Résultat : `success` ou `error`.', type: 'string')]
    #[QueryParameter('days', description: 'Limiter aux N derniers jours (ex. `1`, `7`, `30`).', type: 'integer')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request, ActivityLog $log): JsonResponse
    {
        $activities = $this->paginateList($log->query($request->query()), $request)
            ->through(fn (McpActivity $activity): array => $log->present($activity));

        return response()->json([...$activities->toArray(), 'filters' => $log->filterOptions()]);
    }
}

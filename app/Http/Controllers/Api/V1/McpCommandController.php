<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mcp\CommandGuide;
use Illuminate\Http\JsonResponse;

/**
 * @tags Serveur MCP
 */
class McpCommandController extends Controller
{
    /**
     * Commandes du serveur MCP
     *
     * L'adresse du serveur MCP du portfolio et son mode d'emploi, comme la page « Serveur MCP »
     * de l'administration : les commandes (`prompts`, lancées soi-même dans Claude Code ou
     * Codex) et les outils (`tools`, appelés par l'agent sur une demande en langage naturel).
     * Pour chacun : titre, description transmise à l'agent, usage, conseils (`tips`), exemple
     * de commande (`example`), phrases à écrire telles quelles dans claude.ai ou ChatGPT, mobile
     * compris (`requests`), et arguments des commandes.
     *
     * @response array{data: array{server_url: string, prompts: array<int, array{name: string, title: string, kind: string, description: string, usage: string, tips: array<int, string>, example: string, requests: array<int, string>, arguments: array<int, array{name: string, description: string, required: bool}>}>, tools: array<int, array{name: string, title: string, kind: string, description: string, usage: string, tips: array<int, string>, example: string, requests: array<int, string>, arguments: array<int, array{name: string, description: string, required: bool}>}>}}
     */
    public function index(CommandGuide $guide): JsonResponse
    {
        return response()->json(['data' => [
            'server_url' => url('/mcp/portfolio'),
            ...$guide->all(),
        ]]);
    }
}

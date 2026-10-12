<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mcp\CommandGuide;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Page « Serveur MCP » : adresse du serveur et commandes disponibles, avec leur mode d'emploi.
 * Les jetons et connecteurs sont sur la page « Accès » (McpTokenController).
 */
class McpServerController extends Controller
{
    public function __invoke(CommandGuide $guide): Response
    {
        return Inertia::render('admin/mcp-server/index', [
            'serverUrl' => url('/mcp/portfolio'),
            ...$guide->all(),
        ]);
    }
}

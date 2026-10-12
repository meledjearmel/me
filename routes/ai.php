<?php

use App\Http\Middleware\EnsureMcpAccess;
use App\Mcp\Servers\PortfolioServer;
use Laravel\Mcp\Facades\Mcp;

// Découverte OAuth et enregistrement des clients, pour les connecteurs Claude et ChatGPT (Passport).
Mcp::oauthRoutes();

// Jeton Sanctum (administration « Serveur MCP » ou php artisan mcp:token) pour Claude Code et
// Codex, ou jeton OAuth pour les connecteurs Claude et ChatGPT.
Mcp::web('/mcp/portfolio', PortfolioServer::class)
    ->middleware(['auth:sanctum,api', EnsureMcpAccess::class, 'throttle:60,1']);

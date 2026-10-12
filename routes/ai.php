<?php

use App\Http\Middleware\RejectMcpTokens;
use App\Mcp\Servers\PortfolioServer;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

// Jeton créé dans l'administration (Serveur MCP) ou avec php artisan mcp:token.
Mcp::web('/mcp/portfolio', PortfolioServer::class)
    ->middleware(['auth:sanctum', CheckForAnyAbility::class.':'.RejectMcpTokens::ABILITY, 'throttle:60,1']);

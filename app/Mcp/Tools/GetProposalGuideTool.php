<?php

namespace App\Mcp\Tools;

use App\Mcp\Prompts\WriteProposalPrompt;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-proposal-guide')]
#[Description('La méthode du développeur pour rédiger une proposition freelance (Upwork, Malt…) : profil, règles, déroulé et cadre en 4 piliers. Lis-la avant de rédiger une proposition et suis-la à la lettre. Elle ne fait que rédiger : n\'enregistre rien, save-proposal est une étape séparée, sur demande du développeur.')]
#[IsReadOnly]
class GetProposalGuideTool extends Tool
{
    public function handle(Request $request): Response
    {
        return Response::text(WriteProposalPrompt::guide());
    }
}

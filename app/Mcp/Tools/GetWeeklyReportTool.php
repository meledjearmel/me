<?php

namespace App\Mcp\Tools;

use App\Services\WeeklyReport;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-weekly-report')]
#[Description('Le bilan des 7 derniers jours du portfolio : visites et visiteurs comparés à la semaine d\'avant (change en %), contenus les plus lus et provenances, demandes (messages, freelance, recrutement, rendez-vous), estimations reçues avec l\'avis de l\'IA, pages de candidature consultées (et CV téléchargés), blog, et ce qui attend une action (todo). Pour répondre à « Comment s\'est passée ma semaine ? » : résume en commençant par ce qui demande une action.')]
#[IsReadOnly]
class GetWeeklyReportTool extends Tool
{
    public function handle(Request $request, WeeklyReport $report): Response
    {
        return Response::json($report->build(withHighlights: false));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}

<?php

namespace App\Mcp\Tools;

use App\Services\EstimatorConfigurator;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-estimator')]
#[Description('Lit la grille de l\'estimateur de projet (types de projet et options, en heures hors marge ou en pourcentage, types visés par chaque option, mode de facturation, grille tarifaire et taux propres, actifs ou non), ses grilles tarifaires et ses réglages (taux horaire global par devise, marge pour imprévus, heures facturables par semaine).')]
#[IsReadOnly]
class GetEstimatorTool extends Tool
{
    public function handle(Request $request, EstimatorConfigurator $configurator): Response
    {
        return Response::json($configurator->snapshot());
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}

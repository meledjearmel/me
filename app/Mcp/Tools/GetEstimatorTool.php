<?php

namespace App\Mcp\Tools;

use App\Models\EstimatorItem;
use App\Models\SiteSetting;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-estimator')]
#[Description('Lit la grille de l\'estimateur de projet (types de projet et options, en heures hors marge ou en pourcentage, types visés par chaque option, mode de facturation, actifs ou non) et ses réglages (taux horaire par devise, marge pour imprévus, heures facturables par semaine).')]
#[IsReadOnly]
class GetEstimatorTool extends Tool
{
    public function handle(Request $request): Response
    {
        $settings = SiteSetting::current();

        return Response::json([
            'settings' => [
                'hourly_rates' => $settings->estimatorRates(),
                'risk_percent' => $settings->estimator_risk_percent,
                'hours_per_week' => $settings->estimator_hours_per_week,
            ],
            'items' => EstimatorItem::query()->with('projectTypes:id')->orderBy('kind', 'desc')->orderBy('sort_order')->get()->map(fn (EstimatorItem $item): array => [
                'id' => $item->id,
                'kind' => $item->kind->value,
                'name' => $item->getTranslations('name'),
                'description' => $item->getTranslations('description'),
                'hours_min' => $item->hours_min,
                'hours_max' => $item->hours_max,
                'pricing' => $item->pricing->value,
                'percent' => $item->percent,
                'billing' => $item->billing?->value,
                // Options : types de projet auxquels elles sont réservées (vide : tous).
                'project_types' => $item->projectTypes->modelKeys(),
                'is_active' => $item->is_active,
            ])->all(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}

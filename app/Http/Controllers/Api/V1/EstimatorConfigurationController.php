<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\EstimatorConfigurationRequest;
use App\Services\EstimatorConfigurator;
use Illuminate\Http\JsonResponse;

/**
 * @tags Estimateur de projet
 */
class EstimatorConfigurationController extends Controller
{
    /**
     * Configurer l'estimateur d'un coup
     *
     * Réglages (`settings` : `hourly_rates`, `risk_percent`, `hours_per_week`) et éléments
     * (`items`) à créer ou modifier (avec `id`), en une seule transaction. Chaque élément
     * peut porter une `ref` pour qu'une option la vise dans `project_types` (identifiants
     * de types existants ou `ref` de types créés dans le même envoi). Une option se chiffre
     * en heures (`pricing` = `hours`) ou en pourcentage des heures du projet (`percent`).
     * Les éléments créés sont masqués, sauf `is_active` à true.
     *
     * @response array{settings_updated: bool, created: list<array{id: int, name: string}>, updated: list<array{id: int, name: string}>}
     */
    public function store(EstimatorConfigurationRequest $request, EstimatorConfigurator $configurator): JsonResponse
    {
        return response()->json($configurator->apply($request->validated()));
    }
}

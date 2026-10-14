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
     * Grilles tarifaires (`rates`) à créer ou modifier dans le même envoi, chacune avec
     * `name_fr`, `name_en`, `hourly_rates` et une `ref` facultative. Un élément suit une grille
     * avec `rate` (identifiant, `ref` d'une grille de l'envoi, ou `0` pour aucune) et peut porter
     * ses propres `hourly_rates` (0 retire une devise). Taux appliqué : ceux de l'élément, sinon
     * sa grille, sinon le taux global ; une option en pourcentage suit le taux du type de projet.
     *
     * @response array{settings_updated: bool, rates: list<array{id: int, name: string}>, created: list<array{id: int, name: string}>, updated: list<array{id: int, name: string}>}
     */
    public function store(EstimatorConfigurationRequest $request, EstimatorConfigurator $configurator): JsonResponse
    {
        return response()->json($configurator->apply($request->validated()));
    }
}

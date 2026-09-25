<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardReport;
use Illuminate\Http\JsonResponse;

/**
 * @tags Tableau de bord
 */
class DashboardController extends Controller
{
    /**
     * Tableau de bord
     *
     * Ce qui attend une action, l'audience sur 30 jours, le contenu, sa répartition,
     * sa santé et les derniers éléments reçus.
     */
    public function __invoke(DashboardReport $report): JsonResponse
    {
        return response()->json($report->toArray());
    }
}

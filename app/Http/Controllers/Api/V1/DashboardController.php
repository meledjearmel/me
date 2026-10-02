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
     * sa santé, les derniers éléments reçus et les téléchargements du CV
     * (`cv_downloads` : volumes, et sur 30 jours les 5 premiers pays et les
     * 5 premières provenances, sous la forme `{label, count}`).
     */
    public function __invoke(DashboardReport $report): JsonResponse
    {
        return response()->json($report->toArray());
    }
}

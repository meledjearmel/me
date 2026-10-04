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
     *
     * Dans `visits`, `visitors` compte les visiteurs uniques sur 30 jours (empreinte
     * anonyme qui change chaque jour, sans cookie) ; `by_source` et `by_device` les
     * répartissent par provenance (campagne, sinon site d'origine, sinon `direct`) et
     * par appareil (`desktop`, `mobile`, `tablet`), sous la forme `{label, count}`.
     * `top_content` classe les articles et projets les plus vus, toutes langues réunies :
     * `{type, title, url, visits, visitors, top_source}`, `type` valant `post` ou `project`.
     *
     * `conversions` rapporte aux visiteurs uniques les objectifs atteints sur 30 jours :
     * `goals` liste `{key, count, rate}` pour `cv_downloads`, `contacts`, `engagements`
     * et `appointments`, `rate` étant un pourcentage à une décimale.
     */
    public function __invoke(DashboardReport $report): JsonResponse
    {
        return response()->json($report->toArray());
    }
}

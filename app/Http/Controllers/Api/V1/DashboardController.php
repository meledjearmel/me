<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardReport;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @tags Tableau de bord
 */
class DashboardController extends Controller
{
    /**
     * Tableau de bord
     *
     * Ce qui attend une action, l'audience sur la période choisie (`days`, 30 jours par
     * défaut), le contenu, sa répartition, sa santé, les derniers éléments reçus et les
     * téléchargements du CV (`cv_downloads` : volumes, et sur la période les 5 premiers
     * pays et les 5 premières provenances, sous la forme `{label, count}`).
     *
     * `visits`, `conversions` et `cv_downloads` rappellent la période : `period_days`
     * (`null` pour `all`) et `since`, la date (AAAA-MM-JJ) de son début, celle de la
     * première visite ou du premier téléchargement pour `all`, `null` sans aucune donnée.
     * Avec `all`, `period` est égal à `total`.
     *
     * Dans `visits`, `daily` est une série sans trou de `{date, count}` dont le pas est
     * donné par `granularity` : `day` jusqu'à 90 jours, `month` sur 365 jours (les 12
     * derniers mois) et sur `all`, `year` quand `all` couvre plus de 3 ans ; `date` est
     * alors le premier jour du mois ou de l'année.
     *
     * `visitors` compte les visiteurs uniques sur la période (empreinte anonyme qui
     * change chaque jour, sans cookie) ; `by_source` et `by_device` les répartissent par
     * provenance (campagne, sinon site d'origine, sinon `direct`) et par appareil
     * (`desktop`, `mobile`, `tablet`), sous la forme `{label, count}`.
     * `top_content` classe les 8 articles et projets les plus vus, toutes langues
     * réunies : `{type, title, url, visits, visitors, top_source}`, `type` valant `post`
     * ou `project`.
     *
     * `conversions` rapporte aux visiteurs uniques les objectifs atteints sur la période :
     * `goals` liste `{key, count, rate}` pour `cv_downloads`, `contacts`, `engagements`
     * et `appointments`, `rate` étant un pourcentage à une décimale.
     *
     * `todo.appointments` compte les demandes de rendez-vous en attente, `todo.comments`
     * les commentaires du blog à modérer.
     *
     * `blog` mesure l'engagement des lecteurs : `views_total` (lectures de tous les articles,
     * depuis le début), `reactions` (`total`, `period`, et `by_type` : `{label, count}` sur la
     * période pour `like`, `love`, `fire`, `idea` et `think`), `comments` (`total`, `period`,
     * et par statut `pending`, `approved`, `rejected`) et `top_posts`, les 5 articles qui ont
     * reçu le plus de réactions et de commentaires sur la période (un commentaire pèse comme
     * trois réactions) : `{id, title, url, views, reactions, comments}`, `views` étant le total
     * des lectures depuis le début.
     */
    #[QueryParameter('days', description: 'Période : `7`, `30`, `90`, `365` ou `all` (depuis la toute première donnée). Ne dépendent pas de la période : `visits.total`, `visits.today`, `cv_downloads.total`, `cv_downloads.with_email`, `blog.views_total`, `blog.reactions.total`, `blog.comments.total` et ses statuts, `todo`, `content`, `distribution`, `health` et `recent`.', type: 'string', default: '30')]
    #[QueryParameter('type', description: 'Ne garde dans `visits.top_content` que les articles (`post`) ou que les projets (`project`), le top 8 étant calculé après le filtre. Absent : les deux mélangés.', type: 'string')]
    public function __invoke(Request $request, DashboardReport $report): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['sometimes', 'string', Rule::in(DashboardReport::PERIODS)],
            'type' => ['sometimes', 'string', Rule::in(DashboardReport::CONTENT_TYPES)],
        ]);

        $days = $validated['days'] ?? (string) DashboardReport::DEFAULT_DAYS;

        return response()->json($report->toArray(
            $days === 'all' ? null : (int) $days,
            $validated['type'] ?? null,
        ));
    }
}

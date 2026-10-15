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
     * `visits`, `conversions`, `estimator` et `cv_downloads` rappellent la période : `period_days`
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
     * `estimator` suit l'estimateur de projet sur la période : `page_visitors` (visiteurs
     * uniques de sa page), `requests` (demandes envoyées avec une estimation) et
     * `conversion_rate` (pourcentage à une décimale) ; entre les deux, `simulators` (visiteurs
     * qui ont fait au moins une simulation) et `simulations` (une par visiteur, jour et
     * configuration), avec `simulation_rate` (simulé / visité) et `request_rate` (demandé /
     * simulé) ; `project_type_funnel` donne, pour les 6 types les plus simulés,
     * `{label, simulators, requests}` ; `by_currency` liste par devise
     * `{currency, count, price_min, price_max, average_min, average_max}` (sommes et moyennes
     * des fourchettes affichées) ; `billing` compte `fixed` et `hourly` ; `project_types`,
     * `features` et `rate_grids` (grille tarifaire du type de projet, connue pour les demandes
     * reçues depuis l'ajout des grilles) donnent les 6 premiers en `{label, count}` ;
     * `ai_gap` compare aux heures affichées celles de l'analyse de l'IA : `analysed` (demandes
     * analysées) et `hours_gap_percent` (écart moyen des milieux de fourchette, positif quand
     * l'IA chiffre plus haut ; `null` sans analyse).
     *
     * `outcomes` résume les issues enregistrées sur la période (voir
     * `PUT /engagements/{id}/outcome`) : `won`, `lost`, `win_rate` (part des signées parmi
     * les issues, `null` sans issue), `by_currency` (`{currency, count, amount, average}`
     * des montants signés) et `estimate_gap` : `compared` (signées dans la devise de leur
     * estimation) et `gap_percent` (écart moyen entre le montant signé et le milieu de la
     * fourchette estimée, positif quand c'est signé au-dessus ; `null` sans comparaison).
     *
     * `appointments` porte sur les demandes de rendez-vous reçues sur la période : `total`,
     * `by_status` (`pending`, `confirmed`, `declined`, `cancelled`), `confirmation_rate`
     * (confirmées parmi les confirmées et refusées, `null` sinon), `response_hours` (délai
     * moyen entre la demande et la confirmation, `null` sans confirmation), `by_type` et
     * `by_location` (`video`, `phone`, `whatsapp`, `in_person`) en `{label, count}`.
     *
     * `billing` suit les devis et factures (les proformas ne comptent pas) : `by_currency` liste
     * `{currency, invoiced, collected, outstanding, overdue}` — facturé (émis sur la période),
     * encaissé (payé sur la période), à encaisser et en retard (échéance passée), ces deux-là
     * toutes périodes confondues ; `quotes` donne les devis émis sur la période (`issued`,
     * `accepted`, `declined`, `acceptance_rate` parmi ceux qui ont une réponse, `null` sinon) ;
     * `overdue_invoices` les 5 factures en retard les plus anciennes :
     * `{id, number, client, total, currency, due_at, days_late}`. `todo.overdue_invoices` les compte,
     * `todo.document_reminders` compte les relances suggérées (devis sans réponse, factures en retard).
     *
     * `todo.appointments` compte les demandes de rendez-vous en attente, `todo.comments`
     * les commentaires du blog à modérer. `todo.project_drafts`, `todo.post_drafts` et
     * `todo.content_drafts` (expériences, formations, certifications, page « Uses »,
     * compétences) comptent les brouillons à relire, souvent proposés par l'IA, et
     * `todo.revisions` les modifications de contenus en ligne proposées par l'IA en attente.
     *
     * `blog` mesure l'engagement des lecteurs : `views_total` (lectures de tous les articles,
     * depuis le début), `reactions` (`total`, `period`, et `by_type` : `{label, count}` sur la
     * période pour `like`, `love`, `fire`, `idea` et `think`), `shares` (`total`, `period`, et
     * `by_network` : `{label, count}` sur la période pour `linkedin`, `x`, `whatsapp`, `facebook`,
     * `email`, `copy` (lien copié) et `native` (feuille de partage du téléphone)), `comments`
     * (`total`, `period`, et par statut `pending`, `approved`, `rejected`) et `top_posts`, les
     * 5 articles qui ont suscité le plus d'engagement sur la période (un partage pèse comme deux
     * réactions, un commentaire comme trois) : `{id, title, url, views, reactions, shares, comments}`,
     * `views` étant le total des lectures depuis le début.
     *
     * `trends` compare la période à la précédente de même durée (`null` avec `all`) :
     * `period_days`, `previous_since` (début de la période précédente) et `metrics`, qui donne
     * `{current, previous}` pour `visits`, `visitors`, `contacts`, `engagements`,
     * `appointments` et `cv_downloads` (éléments reçus).
     *
     * `agenda` liste les 5 prochains rendez-vous en attente ou confirmés, les plus proches
     * d'abord : `{id, name, company, type, location, status, starts_at, ends_at}` (ISO 8601).
     */
    #[QueryParameter('days', description: 'Période : `7`, `30`, `90`, `365` ou `all` (depuis la toute première donnée). Ne dépendent pas de la période : `visits.total`, `visits.today`, `cv_downloads.total`, `cv_downloads.with_email`, `blog.views_total`, `blog.reactions.total`, `blog.shares.total`, `blog.comments.total` et ses statuts, `todo`, `content`, `distribution`, `health` et `recent`.', type: 'string', default: '30')]
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

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\WeeklyReport;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Rapport de la semaine
 */
class WeeklyReportController extends Controller
{
    /**
     * Rapport de la semaine
     *
     * Les 7 derniers jours : audience comparée à la semaine d'avant (`change` en %), demandes
     * et estimations reçues avec l'avis de l'IA, pages de candidature consultées, CV, blog et
     * ce qui attend une action. Avec `highlights=1`, l'IA ajoute trois points à retenir (plus
     * lent ; `highlights` vaut null si aucun fournisseur ne répond).
     *
     * @response array{enabled: bool, sent_at: string|null, report: array<string, mixed>}
     */
    #[QueryParameter('highlights', description: '`1` pour ajouter les trois points à retenir rédigés par l\'IA.', type: 'boolean', default: false)]
    public function show(Request $request, WeeklyReport $report): JsonResponse
    {
        $settings = SiteSetting::current();

        return response()->json([
            'enabled' => $settings->weekly_report_enabled,
            'sent_at' => $settings->weekly_report_sent_at?->toIso8601String(),
            'report' => $report->build(withHighlights: $request->boolean('highlights')),
        ]);
    }

    /**
     * Activer ou couper le rapport
     *
     * Activé, il part chaque lundi matin à l'email du profil.
     *
     * @response array{enabled: bool}
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate(['enabled' => ['required', 'boolean']]);

        SiteSetting::current()->update(['weekly_report_enabled' => $request->boolean('enabled')]);

        return response()->json(['enabled' => $request->boolean('enabled')]);
    }

    /**
     * Envoyer le rapport maintenant
     *
     * Envoie le rapport à l'email du profil, même désactivé ou déjà envoyé cette semaine.
     * `status` : `sent`, ou `no_recipient` sans email sur le profil.
     *
     * @response array{status: string}
     */
    public function send(WeeklyReport $report): JsonResponse
    {
        return response()->json(['status' => $report->send(force: true)]);
    }
}

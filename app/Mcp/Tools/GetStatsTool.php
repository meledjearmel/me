<?php

namespace App\Mcp\Tools;

use App\Services\DashboardReport;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-stats')]
#[Description('Les statistiques du portfolio sur une période (7, 30, 90 ou 365 jours) : visites et visiteurs, provenances et appareils, pages et contenus les plus vus, conversions (CV, messages, collaborations, rendez-vous) rapportées aux visiteurs, estimateur de projet (visiteurs de sa page, demandes envoyées et taux de conversion, montants estimés par devise, types, options, mode et grille tarifaire les plus demandés, écart des heures avec l\'analyse de l\'IA), demandes signées ou perdues (taux de signature, montants signés par devise, écart avec l\'estimation), rendez-vous (statuts, taux de confirmation, délai de réponse, types et lieux), téléchargements du CV par pays et provenance, et engagement du blog (lectures, réactions, partages, commentaires). Pour le bilan de la semaine avec les demandes et candidatures, préfère get-weekly-report.')]
#[IsReadOnly]
class GetStatsTool extends Tool
{
    public function handle(Request $request, DashboardReport $report): Response
    {
        $data = $request->validate(['days' => ['nullable', 'integer', 'in:7,30,90,365']]);
        $dashboard = $report->toArray((int) ($data['days'] ?? DashboardReport::DEFAULT_DAYS));

        // La courbe jour par jour alourdit la réponse sans aider à répondre : on la résume.
        $visits = collect($dashboard['visits'])->except('daily')->all();
        $visits['busiest_day'] = collect($dashboard['visits']['daily'] ?? [])->sortByDesc('count')->first();

        return Response::json([
            'visits' => $visits,
            'conversions' => $dashboard['conversions'],
            'estimator' => $dashboard['estimator'],
            'outcomes' => $dashboard['outcomes'],
            'appointments' => $dashboard['appointments'],
            'cv_downloads' => $dashboard['cv_downloads'],
            'blog' => $dashboard['blog'],
            'todo' => $dashboard['todo'],
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()->enum([7, 30, 90, 365])->description('Période en jours (30 par défaut).'),
        ];
    }
}

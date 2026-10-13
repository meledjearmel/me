<?php

namespace App\Mcp\Tools;

use App\Models\Engagement;
use App\Services\Ai\EstimateAssistant;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('save-estimate-analysis')]
#[Description('Enregistre ton analyse d\'une demande de l\'estimateur (voir list-estimate-requests) sur sa fiche dans l\'administration, à la place de l\'analyse existante : résumé, risques, points flous, questions à poser au client, heures réalistes marge comprise et facturation recommandée. Rien n\'est envoyé au client.')]
class SaveEstimateAnalysisTool extends Tool
{
    public function handle(Request $request, EstimateAssistant $assistant): Response
    {
        $request->validate([
            'id' => ['required', 'integer'],
            'summary' => ['required', 'string'],
            'hours_min' => ['required', 'integer', 'min:0', 'max:5000'],
            'hours_max' => ['required', 'integer', 'min:0', 'max:5000'],
            'billing' => ['required', 'in:fixed,hourly'],
            'risks' => ['nullable', 'array'],
            'unclear_points' => ['nullable', 'array'],
            'questions' => ['nullable', 'array'],
            'reasoning' => ['nullable', 'string'],
        ]);

        $engagement = Engagement::query()->whereNotNull('estimate')->find($request->get('id'));

        if ($engagement === null) {
            return Response::error('Demande de l\'estimateur introuvable.');
        }

        $analysis = EstimateAssistant::normalizeAnalysis($request->all());

        if ($analysis === null) {
            return Response::error('Analyse incomplète : il faut au moins un résumé et des heures.');
        }

        $assistant->saveAnalysis($engagement, $analysis);

        return Response::text('Analyse enregistrée : '.route('admin.engagements.show', $engagement));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('La demande analysée.')->required(),
            'summary' => $schema->string()->description('Le besoin en 1 à 3 phrases.')->required(),
            'risks' => $schema->array()->items($schema->string())->description('Ce qui peut faire déraper le temps passé.'),
            'unclear_points' => $schema->array()->items($schema->string())->description('Ce qui reste flou dans la demande.'),
            'questions' => $schema->array()->items($schema->string())->description('Questions à poser au client avant le devis.'),
            'hours_min' => $schema->integer()->description('Heures réalistes au minimum, marge comprise.')->required(),
            'hours_max' => $schema->integer()->description('Heures réalistes au maximum, marge comprise.')->required(),
            'billing' => $schema->string()->enum(['fixed', 'hourly'])->description('hourly quand le périmètre est trop flou ou évolutif pour un forfait sûr.')->required(),
            'reasoning' => $schema->string()->description('Pourquoi ces heures et ce mode, 2 à 4 phrases.'),
        ];
    }
}

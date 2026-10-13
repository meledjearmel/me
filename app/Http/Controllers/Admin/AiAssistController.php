<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstimatorItemKind;
use App\Enums\TextTone;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\DescribeTechnologyRequest;
use App\Http\Requests\Ai\DraftProjectRequest;
use App\Http\Requests\Ai\ImproveTextRequest;
use App\Http\Requests\Ai\ProposeEstimatorGridRequest;
use App\Http\Requests\Ai\SuggestEstimatorItemRequest;
use App\Http\Requests\Ai\TranslateHtmlRequest;
use App\Http\Requests\Ai\TranslateTextRequest;
use App\Http\Requests\Ai\WritePostRequest;
use App\Services\Ai\EstimateAssistant;
use App\Services\Ai\ProjectDraftService;
use App\Services\Ai\TextAssistService;
use Illuminate\Http\JsonResponse;

class AiAssistController extends Controller
{
    public function __construct(private readonly TextAssistService $assist) {}

    public function translate(TranslateTextRequest $request): JsonResponse
    {
        $text = $this->assist->translate(
            $request->validated('text'),
            $request->validated('source_locale'),
            $request->validated('target_locale'),
        );

        return $this->respond($text);
    }

    public function improve(ImproveTextRequest $request): JsonResponse
    {
        $tone = $request->validated('tone');

        $text = $this->assist->improve(
            $request->validated('text'),
            $request->validated('locale'),
            $tone !== null ? TextTone::from($tone) : null,
            $request->validated('instructions'),
        );

        return $this->respond($text);
    }

    public function translateHtml(TranslateHtmlRequest $request): JsonResponse
    {
        return $this->respond($this->assist->translateHtml(
            $request->validated('html'),
            $request->validated('source_locale'),
            $request->validated('target_locale'),
        ));
    }

    /** Barre IA de l'éditeur d'articles : réécrit la sélection ou rédige au curseur. */
    public function writePost(WritePostRequest $request): JsonResponse
    {
        return $this->respond($this->assist->writePost(
            $request->validated('instruction'),
            $request->validated('locale'),
            $request->safe()->only(['title', 'excerpt', 'selection', 'context']),
        ));
    }

    public function describeTechnology(DescribeTechnologyRequest $request): JsonResponse
    {
        $description = $this->assist->describeTechnology(
            $request->validated('name'),
            $request->validated('category'),
        );

        if ($description === null) {
            return $this->respond(null);
        }

        return response()->json(['description' => $description]);
    }

    /** « Pré-remplir avec l'IA » du formulaire de projet : champs en français, à relire. */
    public function draftProject(DraftProjectRequest $request, ProjectDraftService $drafts): JsonResponse
    {
        $project = $drafts->draft($request->validated('github_repository'), $request->validated('notes'));

        if ($project === null) {
            return $this->respond(null);
        }

        return response()->json(['project' => $project]);
    }

    /** « Proposer avec l'IA » du formulaire de l'estimateur : nom, description et heures, à relire. */
    public function suggestEstimatorItem(SuggestEstimatorItemRequest $request, EstimateAssistant $assistant): JsonResponse
    {
        $item = $assistant->suggestItem(
            EstimatorItemKind::from($request->validated('kind')),
            $request->validated('name'),
            $request->validated('notes'),
        );

        if ($item === null) {
            return $this->respond(null);
        }

        return response()->json(['item' => $item]);
    }

    /** « Configurer avec l'IA » de l'estimateur : une grille complète, à relire avant de l'appliquer. */
    public function proposeEstimatorGrid(ProposeEstimatorGridRequest $request, EstimateAssistant $assistant): JsonResponse
    {
        $proposal = $assistant->proposeGrid($request->validated('brief'));

        if ($proposal === null) {
            return $this->respond(null);
        }

        return response()->json(['proposal' => $proposal]);
    }

    private function respond(?string $text): JsonResponse
    {
        if ($text === null) {
            return response()->json([
                'message' => __('L\'assistance IA est momentanément indisponible.'),
            ], 503);
        }

        return response()->json(['text' => $text]);
    }
}

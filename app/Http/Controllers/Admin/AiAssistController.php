<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TextTone;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\DescribeTechnologyRequest;
use App\Http\Requests\Ai\ImproveTextRequest;
use App\Http\Requests\Ai\TranslateHtmlRequest;
use App\Http\Requests\Ai\TranslateTextRequest;
use App\Http\Requests\Ai\WritePostRequest;
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

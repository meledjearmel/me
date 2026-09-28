<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TextTone;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\DescribeTechnologyRequest;
use App\Http\Requests\Ai\ImproveTextRequest;
use App\Http\Requests\Ai\TranslateTextRequest;
use App\Services\Ai\TextAssistService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Assistance IA
 */
class AiController extends Controller
{
    public function __construct(private readonly TextAssistService $assist) {}

    /**
     * Traduire un texte
     *
     * Traduit un texte entre français et anglais, pour compléter un champ bilingue.
     *
     * @response array{text: string}
     */
    public function translate(TranslateTextRequest $request): JsonResponse
    {
        $text = $this->assist->translate(
            $request->validated('text'),
            $request->validated('source_locale'),
            $request->validated('target_locale'),
        );

        return $this->respond($text);
    }

    /**
     * Améliorer un texte
     *
     * Réécrit un texte existant dans sa langue d'origine, avec un ton optionnel
     * (`formal`, `friendly`, `concise` ou `enthusiastic`) et une consigne libre.
     *
     * @response array{text: string}
     */
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

    /**
     * Générer la description d'une technologie
     *
     * Rédige, à partir du nom de la technologie (et de sa catégorie si elle est
     * fournie), une courte description dans l'esprit de la stack du portfolio,
     * en français puis traduite en anglais. Elle alimente le champ bilingue
     * `description` d'une technologie.
     *
     * @response array{description: array{fr: string, en: string}}
     * @response 503 array{message: string}
     */
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

    /**
     * @response 503 array{message: string}
     */
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

<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstimatorItemKind;
use App\Enums\TextTone;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\DescribeTechnologyRequest;
use App\Http\Requests\Ai\DraftProjectRequest;
use App\Http\Requests\Ai\ImproveTextRequest;
use App\Http\Requests\Ai\SuggestEstimatorItemRequest;
use App\Http\Requests\Ai\TranslateHtmlRequest;
use App\Http\Requests\Ai\TranslateTextRequest;
use App\Http\Requests\Ai\WritePostRequest;
use App\Services\Ai\EstimateAssistant;
use App\Services\Ai\ProjectDraftService;
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
     * Traduire un fragment d'article
     *
     * Traduit un fragment HTML d'article du blog en conservant ses balises (6 000 caractères au plus :
     * découper un long article par blocs).
     *
     * @response array{text: string}
     */
    public function translateHtml(TranslateHtmlRequest $request): JsonResponse
    {
        return $this->respond($this->assist->translateHtml(
            $request->validated('html'),
            $request->validated('source_locale'),
            $request->validated('target_locale'),
        ));
    }

    /**
     * Rédiger dans un article
     *
     * Réécrit `selection` (HTML) selon la consigne, ou, sans sélection, rédige le passage à insérer
     * après `context` (texte qui précède le curseur). Renvoie du HTML.
     *
     * @response array{text: string}
     */
    public function writePost(WritePostRequest $request): JsonResponse
    {
        return $this->respond($this->assist->writePost(
            $request->validated('instruction'),
            $request->validated('locale'),
            $request->safe()->only(['title', 'excerpt', 'selection', 'context']),
        ));
    }

    /**
     * Pré-remplir un projet
     *
     * Propose, en français, les champs d'un nouveau projet à partir d'un dépôt GitHub public
     * et/ou de notes, pour pré-remplir le formulaire : rien n'est enregistré. Les champs anglais
     * restent à traduire (`ai/translate`) après relecture. `technologies`, `domains` et
     * `job_profiles` ne contiennent que des identifiants existants. Avec un dépôt, la réponse
     * ajoute `github_repository`, `repo_url`, `demo_url` et `is_open_source`. Un dépôt privé est
     * refusé (422) : il n'est pas envoyé aux fournisseurs d'IA gratuits.
     *
     * @response array{project: array{title: string, slug: string, tagline: string|null, role: string|null, client: string|null, platform: string|null, context: string, challenges: string|null, realization: string|null, result: string|null, decisions: array<int, array{choice: string, reason: string}>, key_figures: array<int, array{value: string, label: string}>, started_on: string|null, ended_on: string|null, team_size: int|null, technologies: array<int, int>, domains: array<int, int>, job_profiles: array<int, int>, github_repository?: string, repo_url?: string, demo_url?: string|null, is_open_source?: bool}}
     * @response 503 array{message: string}
     */
    public function draftProject(DraftProjectRequest $request, ProjectDraftService $drafts): JsonResponse
    {
        $project = $drafts->draft($request->validated('github_repository'), $request->validated('notes'));

        if ($project === null) {
            return $this->respond(null);
        }

        return response()->json(['project' => $project]);
    }

    /**
     * Proposer un élément de l'estimateur
     *
     * Nom bilingue, description et fourchette d'heures (hors marge) d'un type de projet ou
     * d'une option, cohérents avec la grille existante. Rien n'est enregistré.
     *
     * @response array{item: array{name: array{fr: string, en: string}, description: array{fr: string|null, en: string|null}, hours_min: int, hours_max: int, reasoning: string|null}}
     */
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

<?php

namespace App\Http\Controllers;

use App\Enums\EngagementStatus;
use App\Enums\EngagementType;
use App\Enums\EstimatorItemKind;
use App\Http\Requests\ProjectEstimateRequest;
use App\Http\Requests\SuggestEstimateRequest;
use App\Http\Resources\Public\EstimatorItemResource;
use App\Jobs\AnalyzeEngagement;
use App\Jobs\SendEngagementMails;
use App\Jobs\SendPushNotification;
use App\Models\Engagement;
use App\Models\EstimatorItem;
use App\Services\Ai\EstimateAssistant;
use App\Services\GeoLocator;
use App\Services\ProjectEstimator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

/**
 * L'estimateur de projet côté visiteur : il choisit un type de projet et des options (ou
 * laisse l'IA les choisir d'après sa description), voit une fourchette de prix et de délai,
 * puis peut envoyer sa demande, qui arrive dans les collaborations avec l'estimation figée
 * et une analyse de l'IA.
 */
class ProjectEstimateController extends Controller
{
    public function __construct(private ProjectEstimator $estimator) {}

    public function index(Request $request, GeoLocator $geo): Response
    {
        abort_unless($this->estimator->isAvailable(), 404);

        $items = EstimatorItem::query()->active()->with('projectTypes:id')->get();

        return Inertia::render('public/estimate', [
            'projectTypes' => EstimatorItemResource::collection($items->where('kind', EstimatorItemKind::ProjectType)->values()),
            'features' => EstimatorItemResource::collection($items->where('kind', EstimatorItemKind::Feature)->values()),
            'currencies' => $this->estimator->currencies(),
            // La devise du pays du visiteur (d'après son adresse IP), choisie d'office.
            'defaultCurrency' => $this->estimator->defaultCurrency($geo->locate($request->ip())['country_code'], app()->getLocale()),
        ]);
    }

    /** L'estimation pour les choix en cours, recalculée côté serveur à chaque changement. */
    public function quote(ProjectEstimateRequest $request): JsonResponse
    {
        return response()->json(Arr::only($this->estimateFor($request), [
            'billing', 'currency', 'hourly_rate', 'hours_min', 'hours_max', 'price_min', 'price_max', 'weeks_min', 'weeks_max',
        ]));
    }

    /** « Laisser l'IA choisir » : le type et les options qui correspondent à la description du visiteur. */
    public function suggest(SuggestEstimateRequest $request, EstimateAssistant $assistant): JsonResponse
    {
        abort_unless($this->estimator->isAvailable(), 404);

        $match = $assistant->match($request->validated('description'));

        if ($match === null) {
            return response()->json(['message' => __('L\'assistance IA est momentanément indisponible.')], 503);
        }

        return response()->json($match);
    }

    public function store(ProjectEstimateRequest $request): RedirectResponse
    {
        $estimate = $this->estimateFor($request);

        $engagement = Engagement::query()->create([
            'type' => EngagementType::Freelance,
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'company' => $request->validated('company'),
            'subject' => $estimate['project_type'],
            'timeline' => $request->validated('timeline'),
            'estimate' => $estimate,
            'message' => $request->validated('message'),
            'locale' => app()->getLocale(),
            'status' => EngagementStatus::New,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        SendEngagementMails::dispatch($engagement->id);
        SendPushNotification::dispatch('Nouvelle estimation', "{$engagement->subject} · {$engagement->estimate_label}", ['type' => 'engagement', 'id' => (string) $engagement->id]);
        AnalyzeEngagement::dispatch($engagement->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Merci ! Je reviens vers vous très vite.')]);

        return back();
    }

    /** @return array{billing: string, currency: string, hourly_rate: int, risk_percent: int, project_type: string, features: list<string>, hours_min: int, hours_max: int, price_min: int, price_max: int, weeks_min: int, weeks_max: int} */
    private function estimateFor(ProjectEstimateRequest $request): array
    {
        $features = EstimatorItem::query()
            ->whereIn('id', $request->validated('feature_ids') ?? [])
            ->orderBy('sort_order')
            ->get();

        return $this->estimator->estimate(
            EstimatorItem::query()->findOrFail($request->validated('project_type_id')),
            $features,
            $request->validated('currency'),
        );
    }
}

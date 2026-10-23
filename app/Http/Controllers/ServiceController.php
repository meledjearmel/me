<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\TestimonialStatus;
use App\Http\Resources\Public\ProjectResource;
use App\Http\Resources\Public\TestimonialResource;
use App\Models\Project;
use App\Models\Service;
use App\Models\Testimonial;
use App\Services\GeoLocator;
use App\Services\PostMentions;
use App\Services\ProjectEstimator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Page « Services » : les offres publiées, ce qui est compris, un prix « à partir de »
 * dans la devise du visiteur, les projets qui les illustrent et leurs avis, avec un lien
 * vers l'estimateur (type de projet présélectionné). Sans offre publiée : 404.
 */
class ServiceController extends Controller
{
    public function index(Request $request, string $locale, ProjectEstimator $estimator, GeoLocator $geo): Response
    {
        $services = Service::query()
            ->published()
            ->with(['estimatorItem', 'projects' => fn ($query) => $query->where('status', ProjectStatus::Published)->with(['domains', 'technologies.category'])])
            ->orderBy('sort_order')
            ->get();

        abort_if($services->isEmpty(), HttpResponse::HTTP_NOT_FOUND);

        $currency = $estimator->defaultCurrency($geo->locate($request->ip())['country_code'], $locale) ?? 'CHF';
        $estimatorOpen = $estimator->isAvailable();
        $testimonials = $this->testimonialsByProject($services->flatMap->projects->pluck('id')->unique()->all());

        return Inertia::render('public/services', [
            'services' => $services->map(fn (Service $service): array => [
                'id' => $service->id,
                'slug' => $service->slug,
                'title' => $service->getTranslation('title', $locale),
                'tagline' => $service->getTranslation('tagline', $locale) ?: null,
                'description' => PostMentions::plain($service->getTranslation('description', $locale)) ?: null,
                'includes' => $service->includesList($locale),
                'price' => $estimator->startingPrice($service, $currency),
                // Le lien « Estimer mon projet » présélectionne ce type, s'il est proposé.
                'estimate_type_id' => $estimatorOpen && $service->estimatorItem?->is_active ? $service->estimator_item_id : null,
                'projects' => ProjectResource::collection($service->projects->take(3))->resolve(),
                'testimonial' => ($testimonial = $service->projects->map(fn (Project $project) => $testimonials->get($project->id))->filter()->first())
                    ? (new TestimonialResource($testimonial))->resolve()
                    : null,
            ])->values(),
        ]);
    }

    /**
     * Le meilleur avis approuvé de chaque projet (mis en avant d'abord, puis le plus récent).
     *
     * @param  list<int>  $projectIds
     * @return Collection<int, Testimonial>
     */
    private function testimonialsByProject(array $projectIds): Collection
    {
        $byProject = collect();

        Testimonial::query()
            ->with(['media', ...Testimonial::SUBJECT_RELATIONS])
            ->whereHas('projects', fn ($query) => $query->whereKey($projectIds))
            ->where('status', TestimonialStatus::Approved)
            ->orderByDesc('is_featured')
            ->latest('submitted_at')
            ->get()
            ->each(function (Testimonial $testimonial) use ($byProject, $projectIds): void {
                foreach ($testimonial->projects->modelKeys() as $projectId) {
                    if (in_array($projectId, $projectIds, true) && ! $byProject->has($projectId)) {
                        $byProject->put($projectId, $testimonial);
                    }
                }
            });

        return $byProject;
    }
}

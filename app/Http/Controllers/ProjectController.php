<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\TestimonialStatus;
use App\Http\Resources\Public\DomainResource;
use App\Http\Resources\Public\ProjectResource;
use App\Http\Resources\Public\TestimonialResource;
use App\Models\Domain;
use App\Models\Project;
use App\Models\Testimonial;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ProjectController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('public/projects/index', [
            'projects' => ProjectResource::collection(
                Project::query()
                    ->where('status', ProjectStatus::Published)
                    ->with(['domains', 'technologies.category'])
                    ->orderBy('sort_order')
                    ->get(),
            ),
            'domains' => DomainResource::collection(
                Domain::query()->published()->orderBy('sort_order')->get(),
            ),
        ]);
    }

    public function show(string $locale, Project $project): Response
    {
        abort_unless($project->status === ProjectStatus::Published, HttpResponse::HTTP_NOT_FOUND);

        $project->load(['domains', 'technologies.category', 'relatedProjects']);

        $next = $this->nextProject($project);
        $testimonial = $this->testimonialFor($project);

        return Inertia::render('public/projects/show', [
            'project' => new ProjectResource($project),
            'nextProject' => $next ? new ProjectResource($next) : null,
            'testimonial' => $testimonial ? new TestimonialResource($testimonial) : null,
        ]);
    }

    /** L'avis approuvé laissé sur ce projet : celui mis à la une, sinon le plus récent. */
    private function testimonialFor(Project $project): ?Testimonial
    {
        return Testimonial::query()
            ->with('media')
            ->where('project_id', $project->id)
            ->where('status', TestimonialStatus::Approved)
            ->orderByDesc('is_featured')
            ->latest('submitted_at')
            ->first();
    }

    /**
     * Projet proposé en « À lire ensuite » : le premier projet lié, sinon le
     * suivant dans l'ordre (on reboucle sur le premier après le dernier).
     */
    private function nextProject(Project $project): ?Project
    {
        $related = $project->relatedProjects
            ->filter(fn (Project $other): bool => $other->status === ProjectStatus::Published)
            ->sortBy('sort_order')
            ->first();

        if ($related !== null) {
            return $related;
        }

        $others = Project::query()
            ->where('status', ProjectStatus::Published)
            ->where('id', '!=', $project->id)
            ->orderBy('sort_order');

        return (clone $others)->where('sort_order', '>', $project->sort_order)->first()
            ?? $others->first();
    }
}

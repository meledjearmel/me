<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\TestimonialStatus;
use App\Http\Resources\Public\ProjectResource;
use App\Http\Resources\Public\TestimonialResource;
use App\Models\JobApplication;
use App\Models\Project;
use App\Models\Testimonial;
use App\Services\CvGenerator;
use App\Services\JobApplicationTracker;
use App\Services\PostMentions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Page de candidature vue par l'entreprise : mot d'accompagnement, projets choisis, CV du
 * profil visé à télécharger et avis clients. Privée : seul son lien l'ouvre, elle n'est
 * jamais indexée, et un brouillon, une page archivée ou expirée répond 404.
 */
class JobApplicationController extends Controller
{
    public function show(Request $request, string $locale, string $token, JobApplicationTracker $tracker, PostMentions $mentions): Response|RedirectResponse
    {
        $application = $this->open($token);

        // La page est écrite dans une langue : on y ramène un lien ouvert dans l'autre.
        if ($locale !== $application->locale) {
            return redirect()->route('job-applications.show', ['locale' => $application->locale, 'token' => $token]);
        }

        $tracker->view($application, $request);

        $application->load(['jobProfile', 'projects' => fn ($query) => $query->where('status', ProjectStatus::Published)->with(['domains', 'technologies.category'])]);

        return Inertia::render('public/job-application', [
            'application' => [
                'token' => $application->token,
                'company' => $application->company,
                'contact_name' => $application->contact_name,
                'position' => $application->position,
                'intro' => $application->intro,
                'has_cv' => $application->jobProfile !== null,
                // Cartes des projets cités dans le mot (@[App Station](project:12)), par « type:id ».
                'mentions' => (object) $mentions->cardsInText($locale, $application->intro),
            ],
            'projects' => ProjectResource::collection($application->projects)->resolve(),
            'testimonials' => TestimonialResource::collection($this->testimonials($application))->resolve(),
        ])->toResponse($request)->withHeaders(['X-Robots-Tag' => 'noindex, nofollow']);
    }

    /** Le CV du profil visé, dans la langue de la page ; chaque téléchargement est compté. */
    public function cv(Request $request, string $locale, string $token, CvGenerator $cv, JobApplicationTracker $tracker): Response
    {
        $application = $this->open($token);
        abort_if($application->jobProfile === null, Response::HTTP_NOT_FOUND);

        $tracker->cvDownload($application, $request);

        return response($cv->resolve($application->jobProfile, $application->locale)['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$cv->filename($application->jobProfile).'"',
            'Cache-Control' => 'no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    private function open(string $token): JobApplication
    {
        $application = JobApplication::query()->where('token', $token)->first();

        abort_unless($application?->isOpen(), Response::HTTP_NOT_FOUND);

        return $application;
    }

    /**
     * Deux avis approuvés : d'abord ceux des projets choisis, puis les avis mis en avant.
     *
     * @return Collection<int, Testimonial>
     */
    private function testimonials(JobApplication $application): Collection
    {
        $projectIds = $application->projects->map(fn (Project $project): int => $project->id)->all();

        return Testimonial::query()
            ->with('media')
            ->where('status', TestimonialStatus::Approved)
            ->orderByRaw('CASE WHEN project_id IN ('.(implode(',', $projectIds) ?: '0').') THEN 0 ELSE 1 END')
            ->orderByDesc('is_featured')
            ->latest('submitted_at')
            ->limit(2)
            ->get();
    }
}

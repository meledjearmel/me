<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\TestimonialStatus;
use App\Http\Resources\Public\JobProfileResource;
use App\Http\Resources\Public\ProjectResource;
use App\Http\Resources\Public\TechnologyResource;
use App\Http\Resources\Public\TestimonialResource;
use App\Models\JobProfile;
use App\Models\Project;
use App\Models\Technology;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /** Nombre maximum de projets phares affichés sur l'accueil. */
    private const int FEATURED_LIMIT = 3;

    public function index(): Response
    {
        return Inertia::render('public/home', [
            'jobProfiles' => JobProfileResource::collection(
                JobProfile::query()->published()->orderBy('sort_order')->get(),
            ),
            'featuredProjects' => ProjectResource::collection($this->featuredProjects()),
            'technologies' => TechnologyResource::collection(
                Technology::query()->orderBy('category')->orderBy('name')->get(),
            ),
            'testimonialCount' => Testimonial::query()->where('status', TestimonialStatus::Approved)->count(),
            'testimonials' => TestimonialResource::collection(Testimonial::forHomepage()),
        ]);
    }

    /**
     * Les projets mis en avant ; à défaut, les premiers projets publiés,
     * pour que l'accueil ne soit jamais vide.
     *
     * @return Collection<int, Project>
     */
    private function featuredProjects(): Collection
    {
        $published = Project::query()
            ->where('status', ProjectStatus::Published)
            ->with(['domains', 'technologies'])
            ->orderBy('sort_order');

        $featured = (clone $published)->where('is_featured', true)->limit(self::FEATURED_LIMIT)->get();

        return $featured->isNotEmpty()
            ? $featured
            : $published->limit(self::FEATURED_LIMIT)->get();
    }
}

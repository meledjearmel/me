<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TestimonialRequest;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TestimonialController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/testimonials/index', [
            'testimonials' => $this->paginateList(Testimonial::query()->with(['project', 'experience', 'education'])->latest('submitted_at'), $request, ['author_name', 'author_email', 'author_role', 'content->fr', 'content->en'], ['status', 'is_featured']),
            'filters' => $this->listFilters($request, ['status', 'is_featured']),
        ]);
    }

    public function show(Testimonial $testimonial): Response
    {
        return Inertia::render('admin/testimonials/show', [
            'testimonial' => $testimonial->load(['project', 'experience', 'education']),
            'video' => $testimonial->videoData(),
        ]);
    }

    public function edit(Testimonial $testimonial): Response
    {
        return Inertia::render('admin/testimonials/edit', [
            'testimonial' => $testimonial,
            'video' => $testimonial->videoData(),
            'projects' => Project::query()->orderBy('sort_order')->get(['id', 'title', 'slug']),
            'experiences' => Experience::query()->orderByDesc('start_date')->get(['id', 'company', 'role']),
            'educations' => Education::query()->orderByDesc('start_date')->get(['id', 'institution', 'degree']),
        ]);
    }

    public function update(TestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update($request->safe()->except('video'));

        if ($request->hasFile('video')) {
            $testimonial->attachVideo($request->file('video'));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Avis mis à jour.')]);

        return to_route('admin.testimonials.index');
    }

    /** Retire la vidéo et son aperçu : l'avis redevient un avis texte. */
    public function destroyVideo(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->removeVideo();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vidéo retirée.')]);

        return to_route('admin.testimonials.edit', $testimonial);
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Avis supprimé.')]);

        return to_route('admin.testimonials.index');
    }
}

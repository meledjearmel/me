<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TestimonialRequest;
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
            'testimonials' => $this->paginateList(Testimonial::query()->with('project')->latest('submitted_at'), $request, ['author_name', 'author_email', 'author_role', 'content->fr', 'content->en'], ['status', 'is_featured']),
            'filters' => $this->listFilters($request, ['status', 'is_featured']),
        ]);
    }

    public function show(Testimonial $testimonial): Response
    {
        return Inertia::render('admin/testimonials/show', [
            'testimonial' => $testimonial->load('project'),
        ]);
    }

    public function edit(Testimonial $testimonial): Response
    {
        return Inertia::render('admin/testimonials/edit', [
            'testimonial' => $testimonial,
            'projects' => Project::query()->orderBy('sort_order')->get(['id', 'title', 'slug']),
        ]);
    }

    public function update(TestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Avis mis à jour.')]);

        return to_route('admin.testimonials.index');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Avis supprimé.')]);

        return to_route('admin.testimonials.index');
    }
}

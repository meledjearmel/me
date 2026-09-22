<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TestimonialRequest;
use App\Models\Project;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TestimonialController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/testimonials/index', [
            'testimonials' => Testimonial::query()->with('project')->latest('submitted_at')->get(),
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

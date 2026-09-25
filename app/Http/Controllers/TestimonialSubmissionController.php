<?php

namespace App\Http\Controllers;

use App\Enums\TestimonialStatus;
use App\Http\Requests\TestimonialSubmissionRequest;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class TestimonialSubmissionController extends Controller
{
    /**
     * Un avis déposé depuis le site : il reste « en attente » tant que je ne l'ai
     * pas relu et approuvé dans l'admin, et n'apparaît pas avant.
     */
    public function store(TestimonialSubmissionRequest $request): RedirectResponse
    {
        Testimonial::query()->create([
            'author_name' => $request->validated('author_name'),
            'author_email' => $request->validated('author_email'),
            'author_role' => $request->validated('author_role'),
            'content' => [app()->getLocale() => $request->validated('content')],
            'status' => TestimonialStatus::Pending,
            'submitted_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Merci pour votre avis !')]);

        return back();
    }
}

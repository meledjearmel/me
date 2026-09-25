<?php

namespace App\Http\Controllers;

use App\Enums\TestimonialStatus;
use App\Http\Resources\Public\TestimonialResource;
use App\Models\Testimonial;
use Inertia\Inertia;
use Inertia\Response;

class TestimonialController extends Controller
{
    /** Tous les avis approuvés (l'accueil n'en montre que les trois derniers). */
    public function index(): Response
    {
        return Inertia::render('public/testimonials', [
            'testimonials' => TestimonialResource::collection(
                Testimonial::query()
                    ->where('status', TestimonialStatus::Approved)
                    ->latest('submitted_at')
                    ->get(),
            ),
        ]);
    }
}

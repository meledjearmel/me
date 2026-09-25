<?php

namespace App\Http\Controllers;

use App\Http\Resources\Public\EducationResource;
use App\Http\Resources\Public\ExperienceResource;
use App\Models\Counter;
use App\Models\Education;
use App\Models\Experience;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AboutController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('public/about', [
            'experiences' => ExperienceResource::collection(
                Experience::query()->published()->with('highlights')->orderByRaw('end_date is null desc')->orderByDesc('start_date')->get(),
            ),
            'educations' => EducationResource::collection(
                Education::query()->published()->orderByDesc('end_date')->get(),
            ),
            'congratulations' => Counter::total(Counter::CONGRATULATIONS),
            'yearsOfExperience' => $this->yearsOfExperience(),
        ]);
    }

    /**
     * Années d'expérience : de la première mission à aujourd'hui, arrondies à
     * l'année la plus proche (5 ans et 11 mois s'affichent « 6 »).
     */
    private function yearsOfExperience(): int
    {
        $firstStart = Experience::query()->published()->min('start_date');

        if ($firstStart === null) {
            return 0;
        }

        return (int) round(abs(Carbon::parse($firstStart)->diffInMonths(now())) / 12);
    }
}

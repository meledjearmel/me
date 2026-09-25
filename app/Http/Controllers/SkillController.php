<?php

namespace App\Http\Controllers;

use App\Http\Resources\Public\DomainResource;
use App\Http\Resources\Public\SkillResource;
use App\Http\Resources\Public\TechnologyResource;
use App\Models\Domain;
use App\Models\Skill;
use App\Models\Technology;
use Inertia\Inertia;
use Inertia\Response;

class SkillController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('public/skills', [
            'domains' => DomainResource::collection(
                Domain::query()->published()->orderBy('sort_order')->get(),
            ),
            'skills' => SkillResource::collection(
                Skill::query()->published()->whereHas('domain', fn ($query) => $query->published())->with(['domain', 'technologies'])->orderBy('sort_order')->get(),
            ),
            'technologies' => TechnologyResource::collection(
                Technology::query()->orderBy('category')->orderBy('name')->get(),
            ),
        ]);
    }
}

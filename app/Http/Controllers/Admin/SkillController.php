<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SkillRequest;
use App\Models\Domain;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SkillController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/skills/index', [
            'skills' => Skill::query()->with('domain')->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/skills/create', [
            'domains' => Domain::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(SkillRequest $request): RedirectResponse
    {
        Skill::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Compétence créée.')]);

        return to_route('admin.skills.index');
    }

    public function edit(Skill $skill): Response
    {
        return Inertia::render('admin/skills/edit', [
            'skill' => $skill,
            'domains' => Domain::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(SkillRequest $request, Skill $skill): RedirectResponse
    {
        $skill->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Compétence mise à jour.')]);

        return to_route('admin.skills.index');
    }

    public function destroy(Skill $skill): RedirectResponse
    {
        $skill->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Compétence supprimée.')]);

        return to_route('admin.skills.index');
    }
}

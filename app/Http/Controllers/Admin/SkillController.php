<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SkillRequest;
use App\Models\Domain;
use App\Models\Skill;
use App\Models\Technology;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SkillController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/skills/index', [
            'skills' => $this->paginateList(Skill::query()->with('domain')->orderBy('sort_order'), $request, ['name->fr', 'name->en'], ['domain_id', 'status']),
            'filters' => $this->listFilters($request, ['domain_id', 'status']),
            'domains' => Domain::query()->orderBy('sort_order')->get(['id', 'label']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/skills/create', [
            'domains' => Domain::query()->orderBy('sort_order')->get(),
            'technologies' => Technology::query()->orderBy('name')->get(),
        ]);
    }

    public function store(SkillRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $skill = Skill::query()->create($request->safe()->except('technologies'));

            $this->syncTechnologies($skill, $request->validated('technologies', []));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Compétence créée.')]);

        return to_route('admin.skills.index');
    }

    public function show(Skill $skill): Response
    {
        return Inertia::render('admin/skills/show', [
            'skill' => $skill->load(['domain', 'technologies']),
        ]);
    }

    public function edit(Skill $skill): Response
    {
        return Inertia::render('admin/skills/edit', [
            'skill' => $skill->load('technologies'),
            'domains' => Domain::query()->orderBy('sort_order')->get(),
            'technologies' => Technology::query()->orderBy('name')->get(),
        ]);
    }

    public function update(SkillRequest $request, Skill $skill): RedirectResponse
    {
        DB::transaction(function () use ($request, $skill): void {
            $skill->update($request->safe()->except('technologies'));

            $this->syncTechnologies($skill, $request->validated('technologies', []));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Compétence mise à jour.')]);

        return to_route('admin.skills.index');
    }

    public function destroy(Skill $skill): RedirectResponse
    {
        $skill->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Compétence supprimée.')]);

        return to_route('admin.skills.index');
    }

    /**
     * Relie les technologies en gardant l'ordre dans lequel elles ont été cochées.
     *
     * @param  array<int, int|string>  $technologyIds
     */
    private function syncTechnologies(Skill $skill, array $technologyIds): void
    {
        $skill->technologies()->sync(
            collect($technologyIds)
                ->values()
                ->mapWithKeys(fn (int|string $id, int $index): array => [(int) $id => ['sort_order' => $index]])
                ->all(),
        );
    }
}

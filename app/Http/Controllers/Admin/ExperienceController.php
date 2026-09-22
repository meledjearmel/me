<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExperienceRequest;
use App\Models\Experience;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExperienceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/experiences/index', [
            'experiences' => Experience::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/experiences/create');
    }

    public function store(ExperienceRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $experience = Experience::query()->create($request->safe()->except('highlights'));

            $this->syncHighlights($experience, $request->validated('highlights', []));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expérience créée.')]);

        return to_route('admin.experiences.index');
    }

    public function edit(Experience $experience): Response
    {
        return Inertia::render('admin/experiences/edit', [
            'experience' => $experience->load('highlights'),
        ]);
    }

    public function update(ExperienceRequest $request, Experience $experience): RedirectResponse
    {
        DB::transaction(function () use ($request, $experience): void {
            $experience->update($request->safe()->except('highlights'));

            $this->syncHighlights($experience, $request->validated('highlights', []));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expérience mise à jour.')]);

        return to_route('admin.experiences.index');
    }

    public function destroy(Experience $experience): RedirectResponse
    {
        $experience->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expérience supprimée.')]);

        return to_route('admin.experiences.index');
    }

    /**
     * @param  array<int, array{id?: int|null, text: array<string, string>, sort_order: int}>  $highlights
     */
    private function syncHighlights(Experience $experience, array $highlights): void
    {
        $keptIds = [];

        foreach ($highlights as $highlight) {
            $model = $experience->highlights()->updateOrCreate(
                ['id' => $highlight['id'] ?? null],
                [
                    'text' => $highlight['text'],
                    'sort_order' => $highlight['sort_order'],
                ],
            );

            $keptIds[] = $model->id;
        }

        $experience->highlights()->whereNotIn('id', $keptIds)->delete();
    }
}

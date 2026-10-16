<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DistinctionRequest;
use App\Models\Distinction;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Distinctions de la carte « Distinction » de la page À propos, chacune rattachée au besoin à
 * une expérience, une formation ou un projet.
 */
class DistinctionController extends Controller
{
    use PaginatesAdminLists;

    /** @var list<string> */
    private const array FILTERABLE = ['status'];

    public function index(Request $request): Response
    {
        $distinctions = $this->paginateList(
            Distinction::query()->with('distinguishable')->orderBy('sort_order')->orderByDesc('awarded_on'),
            $request,
            ['title->fr', 'title->en', 'issuer'],
            self::FILTERABLE,
        );

        $distinctions->getCollection()->transform(fn (Distinction $distinction): array => $this->row($distinction));

        return Inertia::render('admin/distinctions/index', [
            'distinctions' => $distinctions,
            'filters' => $this->listFilters($request, self::FILTERABLE),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/distinctions/create', [
            'contexts' => $this->contextOptions(),
        ]);
    }

    public function store(DistinctionRequest $request): RedirectResponse
    {
        Distinction::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Distinction ajoutée.')]);

        return to_route('admin.distinctions.index');
    }

    public function edit(Distinction $distinction): Response
    {
        return Inertia::render('admin/distinctions/edit', [
            'distinction' => [
                ...$distinction->toArray(),
                'context_type' => $distinction->contextKey(),
                'context_id' => $distinction->distinguishable_id,
            ],
            'contexts' => $this->contextOptions(),
        ]);
    }

    public function update(DistinctionRequest $request, Distinction $distinction): RedirectResponse
    {
        $distinction->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Distinction mise à jour.')]);

        return to_route('admin.distinctions.index');
    }

    public function destroy(Distinction $distinction): RedirectResponse
    {
        $distinction->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Distinction supprimée.')]);

        return to_route('admin.distinctions.index');
    }

    /**
     * Une ligne de la liste, avec l'intitulé de son contexte.
     *
     * @return array<string, mixed>
     */
    private function row(Distinction $distinction): array
    {
        return [
            ...$distinction->toArray(),
            'context_type' => $distinction->contextKey(),
            'context_label' => $this->contextLabel($distinction),
        ];
    }

    private function contextLabel(Distinction $distinction): ?string
    {
        $context = $distinction->distinguishable;

        return match (true) {
            $context instanceof Experience => $context->company,
            $context instanceof Education => $context->institution,
            $context instanceof Project => $context->getTranslation('title', 'fr'),
            default => null,
        };
    }

    /**
     * Les contextes qu'on peut choisir dans le formulaire, par type.
     *
     * @return array{experience: list<array{id: int, label: string}>, education: list<array{id: int, label: string}>, project: list<array{id: int, label: string}>}
     */
    private function contextOptions(): array
    {
        return [
            'experience' => Experience::query()->orderByDesc('start_date')->get()
                ->map(fn (Experience $experience): array => [
                    'id' => $experience->id,
                    'label' => $experience->company.' — '.$experience->getTranslation('role', 'fr'),
                ])->all(),
            'education' => Education::query()->orderByDesc('start_date')->get()
                ->map(fn (Education $education): array => [
                    'id' => $education->id,
                    'label' => $education->institution.' — '.$education->getTranslation('degree', 'fr'),
                ])->all(),
            'project' => Project::query()->orderBy('sort_order')->get()
                ->map(fn (Project $project): array => [
                    'id' => $project->id,
                    'label' => $project->getTranslation('title', 'fr'),
                ])->all(),
        ];
    }
}

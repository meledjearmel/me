<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Enums\EstimatorItemKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\EstimatorItem;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Les offres de la page « Services » : texte, ce qui est compris, prix « à partir de »
 * et projets qui les illustrent.
 */
class ServiceController extends Controller
{
    use PaginatesAdminLists;

    /** @var list<string> */
    private const array FILTERABLE = ['status'];

    public function index(Request $request): Response
    {
        return Inertia::render('admin/services/index', [
            'services' => $this->paginateList(
                Service::query()->withCount('projects')->orderBy('sort_order'),
                $request,
                ['title->fr', 'title->en', 'slug'],
                self::FILTERABLE,
            ),
            'filters' => $this->listFilters($request, self::FILTERABLE),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/services/create', $this->formOptions());
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        $service = Service::query()->create($request->safe()->except('project_ids'));
        $service->projects()->sync($request->validated('project_ids') ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Offre créée.')]);

        return to_route('admin.services.index');
    }

    public function edit(Service $service): Response
    {
        return Inertia::render('admin/services/edit', [
            'service' => [
                ...$service->toArray(),
                'project_ids' => $service->projects()->pluck('projects.id')->all(),
            ],
            ...$this->formOptions(),
        ]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->safe()->except('project_ids'));
        $service->projects()->sync($request->validated('project_ids') ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Offre mise à jour.')]);

        return to_route('admin.services.index');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Offre supprimée.')]);

        return to_route('admin.services.index');
    }

    /**
     * Les types de projet de l'estimateur et les projets qu'une offre peut citer.
     *
     * @return array{projectTypes: list<array{id: int, label: string}>, projects: list<array{id: int, label: string}>}
     */
    private function formOptions(): array
    {
        return [
            'projectTypes' => EstimatorItem::query()
                ->where('kind', EstimatorItemKind::ProjectType)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (EstimatorItem $item): array => ['id' => $item->id, 'label' => $item->getTranslation('name', 'fr')])
                ->all(),
            'projects' => Project::query()
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Project $project): array => ['id' => $project->id, 'label' => $project->getTranslation('title', 'fr')])
                ->all(),
        ];
    }
}

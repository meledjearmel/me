<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Enums\EstimatorItemKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EstimatorItemRequest;
use App\Models\EstimatorItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** La grille de l'estimateur de projet : types de projet et options, avec leurs prix et délais. */
class EstimatorItemController extends Controller
{
    use PaginatesAdminLists;

    /** Les types de projet, la base de chaque estimation. */
    public function index(Request $request): Response
    {
        return $this->list($request, EstimatorItemKind::ProjectType);
    }

    /** Les options, qui s'ajoutent à un type de projet (sur leur propre page du menu). */
    public function options(Request $request): Response
    {
        return $this->list($request, EstimatorItemKind::Feature);
    }

    private function list(Request $request, EstimatorItemKind $kind): Response
    {
        return Inertia::render('admin/estimator-items/index', [
            'kind' => $kind->value,
            'estimatorItems' => $this->paginateList(
                EstimatorItem::query()->where('kind', $kind)->with('projectTypes:id,name')->orderBy('sort_order'),
                $request,
                ['name->fr', 'name->en'],
            ),
            'filters' => $this->listFilters($request),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/estimator-items/create', [
            'projectTypes' => $this->projectTypeOptions(),
            // « Nouvelle option » ouvre le formulaire sur une option.
            'kind' => (EstimatorItemKind::tryFrom((string) $request->query('kind')) ?? EstimatorItemKind::ProjectType)->value,
        ]);
    }

    public function store(EstimatorItemRequest $request): RedirectResponse
    {
        $item = EstimatorItem::query()->create($request->safe()->except('project_type_ids'));
        $this->syncProjectTypes($item, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Élément de l\'estimateur créé.')]);

        return $this->backToList($item);
    }

    public function edit(EstimatorItem $estimatorItem): Response
    {
        return Inertia::render('admin/estimator-items/edit', [
            'estimatorItem' => [
                ...$estimatorItem->toArray(),
                'project_type_ids' => $estimatorItem->projectTypes()->pluck('estimator_items.id')->all(),
            ],
            'projectTypes' => $this->projectTypeOptions(),
        ]);
    }

    public function update(EstimatorItemRequest $request, EstimatorItem $estimatorItem): RedirectResponse
    {
        $estimatorItem->update($request->safe()->except('project_type_ids'));
        $this->syncProjectTypes($estimatorItem, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Élément de l\'estimateur mis à jour.')]);

        return $this->backToList($estimatorItem);
    }

    /**
     * Les types de projet qu'une option peut viser.
     *
     * @return list<array{id: int, label: string}>
     */
    private function projectTypeOptions(): array
    {
        return EstimatorItem::query()
            ->where('kind', EstimatorItemKind::ProjectType)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (EstimatorItem $item): array => ['id' => $item->id, 'label' => $item->getTranslation('name', 'fr')])
            ->all();
    }

    /** Une option garde les types visés ; un type de projet n'en a jamais. */
    private function syncProjectTypes(EstimatorItem $item, EstimatorItemRequest $request): void
    {
        $item->projectTypes()->sync($item->kind === EstimatorItemKind::Feature ? ($request->validated('project_type_ids') ?? []) : []);
    }

    public function destroy(EstimatorItem $estimatorItem): RedirectResponse
    {
        $estimatorItem->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Élément de l\'estimateur supprimé.')]);

        return $this->backToList($estimatorItem);
    }

    /** Retour à la liste de l'élément : types de projet ou options. */
    private function backToList(EstimatorItem $item): RedirectResponse
    {
        return to_route($item->kind === EstimatorItemKind::Feature ? 'admin.estimator-options.index' : 'admin.estimator-items.index');
    }
}

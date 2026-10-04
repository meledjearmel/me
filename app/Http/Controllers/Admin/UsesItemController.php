<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UsesItemRequest;
use App\Models\UsesItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Éléments de la page « Uses » : matériel, outils, applications et services.
 */
class UsesItemController extends Controller
{
    use PaginatesAdminLists;

    /** @var list<string> */
    private const array FILTERABLE = ['category', 'status'];

    public function index(Request $request): Response
    {
        return Inertia::render('admin/uses-items/index', [
            'items' => $this->paginateList(
                UsesItem::query()->orderBy('category')->orderBy('sort_order'),
                $request,
                ['name', 'description->fr', 'description->en'],
                self::FILTERABLE,
            ),
            'filters' => $this->listFilters($request, self::FILTERABLE),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/uses-items/create');
    }

    public function store(UsesItemRequest $request): RedirectResponse
    {
        UsesItem::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Élément ajouté.')]);

        return to_route('admin.uses-items.index');
    }

    public function edit(UsesItem $usesItem): Response
    {
        return Inertia::render('admin/uses-items/edit', [
            'item' => $usesItem,
        ]);
    }

    public function update(UsesItemRequest $request, UsesItem $usesItem): RedirectResponse
    {
        $usesItem->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Élément mis à jour.')]);

        return to_route('admin.uses-items.index');
    }

    public function destroy(UsesItem $usesItem): RedirectResponse
    {
        $usesItem->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Élément supprimé.')]);

        return to_route('admin.uses-items.index');
    }
}

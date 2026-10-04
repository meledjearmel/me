<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostSeriesRequest;
use App\Models\PostSeries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Séries d'articles : elles naissent depuis le formulaire d'un article ; on corrige
 * ici leur nom français et anglais, ou on les supprime (les articles restent, hors série).
 */
class PostSeriesController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/post-series/index', [
            'series' => $this->paginateList(PostSeries::query()->withCount('posts')->orderBy('slug'), $request, ['slug', 'name->fr', 'name->en']),
            'filters' => $this->listFilters($request),
        ]);
    }

    public function edit(PostSeries $postSeries): Response
    {
        return Inertia::render('admin/post-series/edit', [
            'series' => $postSeries->loadCount('posts'),
        ]);
    }

    public function update(PostSeriesRequest $request, PostSeries $postSeries): RedirectResponse
    {
        $postSeries->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Série mise à jour.')]);

        return to_route('admin.post-series.index');
    }

    public function destroy(PostSeries $postSeries): RedirectResponse
    {
        $postSeries->posts()->update(['series_position' => null]);
        $postSeries->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Série supprimée.')]);

        return to_route('admin.post-series.index');
    }
}

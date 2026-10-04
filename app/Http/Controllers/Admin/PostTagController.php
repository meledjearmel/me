<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostTagRequest;
use App\Models\PostTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tags du blog : ils naissent depuis le formulaire d'un article ; on corrige
 * ici leur nom français et anglais, ou on les supprime. Le slug, utilisé dans
 * les liens de filtre, ne change pas.
 */
class PostTagController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/post-tags/index', [
            'tags' => $this->paginateList(PostTag::query()->withCount('posts')->orderBy('slug'), $request, ['slug', 'name->fr', 'name->en']),
            'filters' => $this->listFilters($request),
        ]);
    }

    public function edit(PostTag $postTag): Response
    {
        return Inertia::render('admin/post-tags/edit', [
            'tag' => $postTag->loadCount('posts'),
        ]);
    }

    public function update(PostTagRequest $request, PostTag $postTag): RedirectResponse
    {
        $postTag->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tag mis à jour.')]);

        return to_route('admin.post-tags.index');
    }

    public function destroy(PostTag $postTag): RedirectResponse
    {
        $postTag->posts()->detach();
        $postTag->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tag supprimé.')]);

        return to_route('admin.post-tags.index');
    }
}

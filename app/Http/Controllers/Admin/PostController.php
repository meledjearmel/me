<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Concerns\SavesPosts;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostImageRequest;
use App\Http\Requests\Admin\PostRequest;
use App\Models\Post;
use App\Models\PostSeries;
use App\Models\PostTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    use PaginatesAdminLists, SavesPosts;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/posts/index', [
            'posts' => $this->paginateList(
                Post::query()->with('tags')->latest('published_at')->latest(),
                $request,
                ['title->fr', 'title->en', 'slug'],
                ['status', 'is_featured'],
            ),
            'filters' => $this->listFilters($request, ['status', 'is_featured']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/posts/create', $this->formOptions());
    }

    public function store(PostRequest $request): RedirectResponse
    {
        $post = DB::transaction(function () use ($request): Post {
            $post = Post::query()->create($this->postAttributes($request));
            $this->syncTags($post, $request);
            $this->syncSeries($post, $request);

            return $post;
        });

        $this->syncCover($post, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Article créé.')]);

        return to_route('admin.posts.edit', $post);
    }

    public function edit(Post $post): Response
    {
        return Inertia::render('admin/posts/edit', [
            'post' => $this->postForEditing($post->load('tags')),
            ...$this->formOptions(),
        ]);
    }

    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        DB::transaction(function () use ($request, $post): void {
            $post->update($this->postAttributes($request, $post));
            $this->syncTags($post, $request);
            $this->syncSeries($post, $request);
        });

        $this->syncCover($post, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Article enregistré.')]);

        return back();
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Article supprimé.')]);

        return to_route('admin.posts.index');
    }

    public function destroyCover(Post $post): RedirectResponse
    {
        $post->clearMediaCollection('cover');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Couverture retirée.')]);

        return back();
    }

    /** Image glissée ou collée dans l'éditeur. */
    public function storeImage(PostImageRequest $request): JsonResponse
    {
        return response()->json(['url' => $this->storeContentImage($request)], 201);
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'tags' => PostTag::query()->orderBy('slug')->get()
                ->map(fn (PostTag $tag): string => $tag->getTranslation('name', 'fr'))
                ->values(),
            'seriesNames' => PostSeries::query()->orderBy('slug')->get()
                ->map(fn (PostSeries $series): string => $series->getTranslation('name', 'fr'))
                ->values(),
        ];
    }
}

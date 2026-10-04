<?php

namespace App\Http\Controllers;

use App\Http\Resources\Public\PostResource;
use App\Models\Post;
use App\Models\PostTag;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BlogController extends Controller
{
    private const int PER_PAGE = 9;

    private const int RELATED_COUNT = 3;

    public function index(Request $request): Response
    {
        $this->ensureBlogIsEnabled();

        $tags = PostTag::query()
            ->whereHas('posts', fn (Builder $query) => $query->published())
            ->withCount(['posts' => fn (Builder $query) => $query->published()])
            ->orderByDesc('posts_count')
            ->get();

        $activeTag = $tags->firstWhere('slug', $request->query('tag'));

        $posts = Post::query()
            ->published()
            ->when($activeTag, fn (Builder $query) => $query->whereHas('tags', fn (Builder $tags) => $tags->whereKey($activeTag->id)))
            ->with(['tags', 'media'])
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('public/blog/index', [
            'posts' => PostResource::collection($posts),
            'tags' => $tags->map(fn (PostTag $tag): array => [
                'slug' => $tag->slug,
                'name' => $tag->getTranslation('name', app()->getLocale()),
                'count' => $tag->posts_count,
            ])->values(),
            'activeTag' => $activeTag?->slug,
            'total' => Post::query()->published()->count(),
        ]);
    }

    public function show(string $locale, Post $post): Response
    {
        $this->ensureBlogIsEnabled();
        abort_unless($post->isPublished(), HttpResponse::HTTP_NOT_FOUND);

        $post->load(['tags', 'media']);

        return Inertia::render('public/blog/show', [
            'post' => (new PostResource($post))->withBody(),
            'relatedPosts' => PostResource::collection($this->relatedPosts($post)),
        ]);
    }

    private function ensureBlogIsEnabled(): void
    {
        abort_unless(SiteSetting::current()->blog_enabled, HttpResponse::HTTP_NOT_FOUND);
    }

    /**
     * Articles proposés en fin de lecture : ceux qui partagent le plus de tags,
     * complétés par les plus récents.
     *
     * @return Collection<int, Post>
     */
    private function relatedPosts(Post $post): Collection
    {
        $tagIds = $post->tags->modelKeys();

        $related = Post::query()
            ->published()
            ->whereKeyNot($post->id)
            ->whereHas('tags', fn (Builder $query) => $query->whereKey($tagIds))
            ->withCount(['tags' => fn (Builder $query) => $query->whereKey($tagIds)])
            ->with(['tags', 'media'])
            ->orderByDesc('tags_count')
            ->latest('published_at')
            ->limit(self::RELATED_COUNT)
            ->get();

        if ($related->count() < self::RELATED_COUNT) {
            $related = $related->concat(
                Post::query()
                    ->published()
                    ->whereKeyNot([$post->id, ...$related->modelKeys()])
                    ->with(['tags', 'media'])
                    ->latest('published_at')
                    ->limit(self::RELATED_COUNT - $related->count())
                    ->get(),
            );
        }

        return $related;
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\Public\PostResource;
use App\Models\Post;
use App\Models\PostTag;
use App\Models\SiteSetting;
use App\Services\TextSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BlogController extends Controller
{
    private const int PER_PAGE = 9;

    private const int RELATED_COUNT = 3;

    public function __construct(private TextSearch $search) {}

    public function index(Request $request): Response
    {
        $this->ensureBlogIsEnabled();

        $query = Str::limit(trim((string) $request->query('q', '')), 100, '');
        $terms = $this->search->terms($query);

        $tags = PostTag::query()
            ->whereHas('posts', fn (Builder $query) => $query->published())
            ->withCount(['posts' => fn (Builder $query) => $query->published()])
            ->orderByDesc('posts_count')
            ->get();

        $activeTag = $tags->firstWhere('slug', $request->query('tag'));

        $posts = Post::query()
            ->published()
            ->when($activeTag, fn (Builder $query) => $query->whereHas('tags', fn (Builder $tags) => $tags->whereKey($activeTag->id)))
            ->when($terms !== [], fn (Builder $query) => $query->whereKey($this->matchingPostIds($terms)))
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
            'search' => $query,
            'total' => Post::query()->published()->count(),
        ]);
    }

    public function show(string $locale, Post $post): Response
    {
        $this->ensureBlogIsEnabled();
        abort_unless($post->isPublished(), HttpResponse::HTTP_NOT_FOUND);

        return $this->renderPost($post, $locale);
    }

    /**
     * Aperçu d'un brouillon ou d'un article programmé, par un lien signé et temporaire
     * (Post::previewUrl) : il s'ouvre sans compte, même quand le blog est désactivé.
     */
    public function preview(string $locale, Post $post): Response
    {
        return $this->renderPost($post, $locale, preview: true);
    }

    private function renderPost(Post $post, string $locale, bool $preview = false): Response
    {
        $post->load(['tags', 'media']);

        return Inertia::render('public/blog/show', [
            'post' => (new PostResource($post))->withBody(),
            'relatedPosts' => PostResource::collection($this->relatedPosts($post)),
            'series' => $this->series($post, $locale),
            'adjacent' => $this->adjacentPosts($post, $locale),
            'preview' => $preview,
        ]);
    }

    /**
     * La série de l'article : son nom et ses parties publiées, dans l'ordre.
     *
     * @return array{name: string, parts: list<array{slug: string, title: string, position: int|null, current: bool}>}|null
     */
    private function series(Post $post, string $locale): ?array
    {
        if ($post->series === null) {
            return null;
        }

        return [
            'name' => $post->series->getTranslation('name', $locale),
            'parts' => $post->series->posts()
                ->published()
                ->orderByRaw('series_position is null')
                ->orderBy('series_position')
                ->orderBy('published_at')
                ->get(['id', 'slug', 'title', 'series_position'])
                ->map(fn (Post $part): array => [
                    'slug' => $part->slug,
                    'title' => $part->getTranslation('title', $locale),
                    'position' => $part->series_position,
                    'current' => $part->is($post),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Article publié juste avant et juste après, par date de publication. Un aperçu sans date
     * n'a que le dernier article publié avant lui.
     *
     * @return array{previous: array{slug: string, title: string}|null, next: array{slug: string, title: string}|null}
     */
    private function adjacentPosts(Post $post, string $locale): array
    {
        $date = $post->published_at ?? now();
        $present = fn (?Post $adjacent): ?array => $adjacent === null ? null : [
            'slug' => $adjacent->slug,
            'title' => $adjacent->getTranslation('title', $locale),
        ];

        return [
            'previous' => $present(Post::query()->published()->whereKeyNot($post->id)
                ->where('published_at', '<=', $date)
                ->orderByDesc('published_at')->orderByDesc('id')
                ->first(['id', 'slug', 'title'])),
            'next' => $present($post->published_at === null ? null : Post::query()->published()->whereKeyNot($post->id)
                ->where('published_at', '>', $date)
                ->orderBy('published_at')->orderBy('id')
                ->first(['id', 'slug', 'title'])),
        ];
    }

    /**
     * Articles publiés dont le titre, l'extrait, les tags ou le contenu contiennent tous les
     * termes, dans l'une ou l'autre langue.
     *
     * @param  list<string>  $terms
     * @return list<int>
     */
    private function matchingPostIds(array $terms): array
    {
        return Post::query()
            ->published()
            ->with('tags')
            ->get(['id', 'title', 'excerpt', 'body'])
            ->filter(fn (Post $post): bool => $this->search->matches($terms, [
                ...array_values($post->getTranslations('title')),
                ...array_values($post->getTranslations('excerpt')),
                ...array_values($post->getTranslations('body')),
                ...$post->tags->flatMap(fn (PostTag $tag): array => array_values($tag->getTranslations('name'))),
            ]))
            ->modelKeys();
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

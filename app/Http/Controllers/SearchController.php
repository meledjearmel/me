<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Models\Post;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\Skill;
use App\Services\TextSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Recherche globale du site public (fenêtre Ctrl+K) : articles, projets et compétences.
 *
 * Le contenu public tient en quelques dizaines de lignes : il est filtré en PHP plutôt
 * qu'en SQL, ce qui ignore accents et casse sur les champs traduits stockés en JSON.
 */
class SearchController extends Controller
{
    private const int LIMIT_PER_TYPE = 5;

    public function __construct(private TextSearch $search) {}

    public function __invoke(Request $request, string $locale): JsonResponse
    {
        $terms = $this->search->terms((string) $request->query('q', ''));

        if ($terms === []) {
            return response()->json(['results' => []]);
        }

        return response()->json(['results' => [
            ...$this->posts($locale, $terms),
            ...$this->projects($locale, $terms),
            ...$this->skills($locale, $terms),
        ]]);
    }

    /**
     * @param  list<string>  $terms
     * @return list<array{type: string, title: string, excerpt: string|null, url: string}>
     */
    private function posts(string $locale, array $terms): array
    {
        if (! SiteSetting::current()->blog_enabled) {
            return [];
        }

        return $this->match(
            Post::query()->published()->with('tags')->latest('published_at')->get(),
            $terms,
            fn (Post $post): array => [
                $post->getTranslation('title', $locale),
                $post->getTranslation('excerpt', $locale),
                ...$post->tags->map(fn ($tag): string => $tag->getTranslation('name', $locale)),
            ],
            fn (Post $post): array => [
                'type' => 'post',
                'title' => $post->getTranslation('title', $locale),
                'excerpt' => $post->getTranslation('excerpt', $locale) ?: null,
                'url' => "/{$locale}/blog/{$post->slug}",
            ],
        );
    }

    /**
     * @param  list<string>  $terms
     * @return list<array{type: string, title: string, excerpt: string|null, url: string}>
     */
    private function projects(string $locale, array $terms): array
    {
        return $this->match(
            Project::query()->where('status', ProjectStatus::Published)->with('technologies')->orderBy('sort_order')->get(),
            $terms,
            fn (Project $project): array => [
                $project->getTranslation('title', $locale),
                $project->getTranslation('tagline', $locale),
                $project->getTranslation('client', $locale),
                $project->getTranslation('platform', $locale),
                ...$project->technologies->pluck('name'),
            ],
            fn (Project $project): array => [
                'type' => 'project',
                'title' => $project->getTranslation('title', $locale),
                'excerpt' => $project->getTranslation('tagline', $locale) ?: null,
                'url' => "/{$locale}/projects/{$project->slug}",
            ],
        );
    }

    /**
     * @param  list<string>  $terms
     * @return list<array{type: string, title: string, excerpt: string|null, url: string}>
     */
    private function skills(string $locale, array $terms): array
    {
        return $this->match(
            Skill::query()->published()->whereHas('domain', fn (Builder $query) => $query->published())->with('domain')->orderBy('sort_order')->get(),
            $terms,
            fn (Skill $skill): array => [
                $skill->getTranslation('name', $locale),
                $skill->getTranslation('description', $locale),
                $skill->domain->getTranslation('label', $locale),
            ],
            fn (Skill $skill): array => [
                'type' => 'skill',
                'title' => $skill->getTranslation('name', $locale),
                'excerpt' => $skill->domain->getTranslation('label', $locale),
                'url' => "/{$locale}/skills#domain-{$skill->domain->key}",
            ],
        );
    }

    /**
     * Garde les éléments dont le texte contient tous les termes, dans la limite par type.
     *
     * @template TModel
     *
     * @param  Collection<int, TModel>  $items
     * @param  list<string>  $terms
     * @param  callable(TModel): iterable<string|null>  $haystack
     * @param  callable(TModel): array{type: string, title: string, excerpt: string|null, url: string}  $present
     * @return list<array{type: string, title: string, excerpt: string|null, url: string}>
     */
    private function match(Collection $items, array $terms, callable $haystack, callable $present): array
    {
        return $items
            ->filter(fn ($item): bool => $this->search->matches($terms, $haystack($item)))
            ->take(self::LIMIT_PER_TYPE)
            ->map($present)
            ->values()
            ->all();
    }
}

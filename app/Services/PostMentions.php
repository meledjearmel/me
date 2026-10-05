<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\Experience;
use App\Models\Post;
use App\Models\Project;
use App\Models\Technology;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Éléments du site qu'on peut mentionner avec « @ » (projets, articles, technologies,
 * expériences).
 * Le contenu ne garde que le type et l'identifiant : le nom, le lien et la carte affichée au
 * survol sont lus à la lecture, et un élément retiré du site redevient du texte simple.
 *
 * Dans un article (HTML), la mention est un nœud de l'éditeur. Dans un texte brut (étude de
 * cas, expérience, page « Now »), c'est un jeton `@[App Station](project:12)`.
 */
class PostMentions
{
    public const array KINDS = ['project', 'post', 'technology', 'experience'];

    private const int SEARCH_LIMIT = 8;

    /** Jeton d'une mention dans un texte brut : libellé, type et identifiant. */
    public const string TOKEN = '/@\[([^\]\n]{1,120})\]\((project|post|technology|experience):(\d+)\)/u';

    /**
     * Suggestions pour l'éditeur : éléments publics dont le nom contient la recherche.
     *
     * @return list<array{kind: string, id: int, label: string, hint: string|null}>
     */
    public function search(string $query): array
    {
        $query = mb_strtolower(trim($query));
        $matches = fn (string $label): bool => $query === '' || str_contains(mb_strtolower($label), $query);

        return collect([
            ...$this->projects()->get()->map(fn (Project $project): array => [
                'kind' => 'project',
                'id' => $project->id,
                'label' => $project->getTranslation('title', 'fr'),
                'hint' => $project->getTranslation('tagline', 'fr') ?: null,
            ]),
            ...$this->posts()->get()->map(fn (Post $post): array => [
                'kind' => 'post',
                'id' => $post->id,
                'label' => $post->getTranslation('title', 'fr'),
                'hint' => $post->getTranslation('excerpt', 'fr') ?: null,
            ]),
            ...Technology::query()->orderBy('name')->get()->map(fn (Technology $technology): array => [
                'kind' => 'technology',
                'id' => $technology->id,
                'label' => $technology->name,
                'hint' => $technology->getTranslation('description', 'fr') ?: null,
            ]),
            ...$this->experiences()->get()->map(fn (Experience $experience): array => [
                'kind' => 'experience',
                'id' => $experience->id,
                'label' => $experience->company,
                'hint' => $experience->getTranslation('role', 'fr') ?: null,
            ]),
        ])
            ->filter(fn (array $item): bool => $matches($item['label']))
            // Les noms qui commencent par la recherche d'abord (tri stable : l'ordre de chaque type est gardé).
            ->sortBy(fn (array $item): int => $query !== '' && str_starts_with(mb_strtolower($item['label']), $query) ? 0 : 1)
            ->groupBy('kind')
            ->pipe(fn (Collection $groups): Collection => $this->interleave($groups))
            ->take(self::SEARCH_LIMIT)
            ->values()
            ->all();
    }

    /**
     * Alterne les types (un projet, un article, une technologie…) pour qu'un type nombreux ne
     * cache pas les autres dans les suggestions.
     *
     * @param  Collection<string, Collection<int, array{kind: string, id: int, label: string, hint: string|null}>>  $groups
     * @return Collection<int, array{kind: string, id: int, label: string, hint: string|null}>
     */
    private function interleave(Collection $groups): Collection
    {
        $groups = $groups->map(fn (Collection $group): array => $group->values()->all())->values();
        $longest = $groups->map(fn (array $group): int => count($group))->max() ?? 0;

        return collect(range(0, max(0, $longest - 1)))
            ->flatMap(fn (int $index): array => $groups->map(fn (array $group): ?array => $group[$index] ?? null)->filter()->all());
    }

    /**
     * Cartes des éléments mentionnés, indexées par « type:id ». Les éléments inconnus ou
     * non publics sont absents.
     *
     * @param  list<array{kind: string, id: int}>  $references
     * @return array<string, array{kind: string, title: string, description: string|null, image: string|null, url: string}>
     */
    public function cards(array $references, string $locale): array
    {
        $ids = collect($references)->groupBy('kind')->map(fn (Collection $group): array => $group->pluck('id')->unique()->all());
        $cards = [];

        if ($ids->has('project')) {
            foreach ($this->projects()->with('media')->whereKey($ids['project'])->get() as $project) {
                $cards["project:{$project->id}"] = [
                    'kind' => 'project',
                    'title' => $project->getTranslation('title', $locale),
                    'description' => $project->getTranslation('tagline', $locale) ?: null,
                    'image' => $project->getFirstMediaUrl('cover') ?: null,
                    'url' => route('projects.show', ['locale' => $locale, 'project' => $project->slug]),
                ];
            }
        }

        if ($ids->has('post')) {
            foreach ($this->posts()->with('media')->whereKey($ids['post'])->get() as $post) {
                $cards["post:{$post->id}"] = [
                    'kind' => 'post',
                    'title' => $post->getTranslation('title', $locale),
                    'description' => $post->getTranslation('excerpt', $locale) ?: null,
                    'image' => $post->getFirstMediaUrl('cover') ?: null,
                    'url' => route('blog.show', ['locale' => $locale, 'post' => $post->slug]),
                ];
            }
        }

        if ($ids->has('technology')) {
            foreach (Technology::query()->whereKey($ids['technology'])->get() as $technology) {
                $cards["technology:{$technology->id}"] = [
                    'kind' => 'technology',
                    'title' => $technology->name,
                    'description' => $technology->getTranslation('description', $locale) ?: null,
                    'image' => null,
                    'url' => route('skills', ['locale' => $locale]),
                ];
            }
        }

        if ($ids->has('experience')) {
            foreach ($this->experiences()->whereKey($ids['experience'])->get() as $experience) {
                $cards["experience:{$experience->id}"] = [
                    'kind' => 'experience',
                    'title' => $experience->company,
                    'description' => collect([
                        $experience->getTranslation('role', $locale),
                        $experience->start_date->year.' – '.($experience->end_date?->year ?? ($locale === 'en' ? 'today' : 'aujourd’hui')),
                    ])->filter()->implode(' · '),
                    'image' => null,
                    'url' => route('about', ['locale' => $locale]),
                ];
            }
        }

        return $cards;
    }

    /**
     * Cartes des mentions contenues dans des textes bruts, par « type:id ».
     *
     * @return array<string, array{kind: string, title: string, description: string|null, image: string|null, url: string}>
     */
    public function cardsInText(string $locale, ?string ...$texts): array
    {
        preg_match_all(self::TOKEN, implode("\n", array_filter($texts)), $matches, PREG_SET_ORDER);

        return $matches === [] ? [] : $this->cards(
            array_map(fn (array $match): array => ['kind' => $match[2], 'id' => (int) $match[3]], $matches),
            $locale,
        );
    }

    /** Texte brut sans jetons : chaque mention redevient son libellé (IA, llms.txt…). */
    public static function plain(?string $text): string
    {
        return (string) preg_replace(self::TOKEN, '$1', (string) $text);
    }

    /**
     * Cartes des pages de projet et d'article du site citées par adresse dans un texte (réponses
     * de l'assistant), indexées par l'adresse telle qu'elle apparaît.
     *
     * @return array<string, array{kind: string, title: string, description: string|null, image: string|null, url: string}>
     */
    public function cardsForUrls(string $text, string $locale): array
    {
        $base = preg_quote(rtrim((string) config('app.url'), '/'), '#');
        preg_match_all("#{$base}/(fr|en)/(projects|blog)/([a-z0-9-]+)#", $text, $matches, PREG_SET_ORDER);

        if ($matches === []) {
            return [];
        }

        $slugs = collect($matches)->groupBy(2)->map(fn (Collection $group): array => $group->pluck(3)->unique()->all());
        $ids = [
            'projects' => $this->projects()->whereIn('slug', $slugs['projects'] ?? [])->pluck('id', 'slug'),
            'blog' => $this->posts()->whereIn('slug', $slugs['blog'] ?? [])->pluck('id', 'slug'),
        ];
        $references = [];

        foreach ($matches as [$url, , $section, $slug]) {
            if (isset($ids[$section][$slug])) {
                $references[$url] = ['kind' => $section === 'projects' ? 'project' : 'post', 'id' => $ids[$section][$slug]];
            }
        }

        $cards = $this->cards(array_values($references), $locale);

        return collect($references)
            ->map(fn (array $reference): ?array => $cards["{$reference['kind']}:{$reference['id']}"] ?? null)
            ->filter()
            ->all();
    }

    /** @return Builder<Project> */
    private function projects(): Builder
    {
        return Project::query()->where('status', ProjectStatus::Published)->orderBy('sort_order');
    }

    /** @return Builder<Experience> */
    private function experiences(): Builder
    {
        return Experience::query()->published()->orderBy('sort_order');
    }

    /** @return Builder<Post> */
    private function posts(): Builder
    {
        return Post::query()->published()->latest('published_at');
    }
}

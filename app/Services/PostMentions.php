<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\Post;
use App\Models\Project;
use App\Models\Technology;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Éléments du site qu'un article peut mentionner avec « @ » (projets, articles, technologies).
 * Le contenu ne garde que le type et l'identifiant : le nom, le lien et la carte affichée au
 * survol sont lus à la lecture, et un élément retiré du site redevient du texte simple.
 */
class PostMentions
{
    public const array KINDS = ['project', 'post', 'technology'];

    private const int SEARCH_LIMIT = 8;

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
        ])
            ->filter(fn (array $item): bool => $matches($item['label']))
            ->take(self::SEARCH_LIMIT)
            ->values()
            ->all();
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

        return $cards;
    }

    /** @return Builder<Project> */
    private function projects(): Builder
    {
        return Project::query()->where('status', ProjectStatus::Published)->orderBy('sort_order');
    }

    /** @return Builder<Post> */
    private function posts(): Builder
    {
        return Post::query()->published()->latest('published_at');
    }
}

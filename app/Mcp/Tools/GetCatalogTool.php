<?php

namespace App\Mcp\Tools;

use App\Models\Post;
use App\Models\PostSeries;
use App\Models\PostTag;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\TechnologyCategory;
use App\Services\Ai\ProjectDraftService;
use App\Services\GitHubActivity;
use App\Services\PostMentions;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-catalog')]
#[Description('Liste les technologies (et leurs catégories), domaines et profils métier du portfolio (identifiant et libellé), les projets et articles existants (identifiant, slug, titre, statut), les tags et séries du blog, et les éléments publics mentionnables (projets, articles, technologies, expériences) avec leur jeton de mention prêt à insérer. À appeler avant d\'enregistrer un brouillon pour choisir les identifiants, citer un élément du site et éviter un slug déjà pris.')]
#[IsReadOnly]
class GetCatalogTool extends Tool
{
    public function handle(Request $request, ProjectDraftService $drafts, PostMentions $mentions, GitHubActivity $github): Response
    {
        $linkedProjects = Project::query()->whereNotNull('github_repository')->get(['id', 'github_repository'])
            ->mapWithKeys(fn (Project $project): array => [strtolower($project->github_repository) => $project->id]);

        return Response::json([
            ...$drafts->catalog(),
            // La page « Now » actuelle, à modifier avec propose-revision (type now_page).
            'now_page' => [
                'fr' => SiteSetting::current()->now_content['fr'] ?? null,
                'en' => SiteSetting::current()->now_content['en'] ?? null,
                'updated_at' => SiteSetting::current()->now_updated_at?->toIso8601String(),
            ],
            // Dépôts de la dernière synchronisation, privés compris, et le projet déjà lié à chacun.
            'github_repositories' => collect($github->cached()['available'] ?? [])
                ->map(fn (array $repository): array => [
                    'full_name' => $repository['full_name'],
                    'private' => $repository['private'],
                    'linked_project_id' => $linkedProjects[strtolower($repository['full_name'])] ?? null,
                ])
                ->sortBy('full_name')
                ->values()
                ->all(),
            'technology_categories' => TechnologyCategory::query()->orderBy('sort_order')->get()
                ->mapWithKeys(fn (TechnologyCategory $category): array => [$category->id => $category->getTranslation('label', 'fr')])
                ->all(),
            'mentionables' => $mentions->mentionables()
                ->map(fn (array $item): array => [
                    'mention' => PostMentions::token($item['kind'], $item['id'], $item['label']),
                    'hint' => $item['hint'],
                ])
                ->values()
                ->all(),
            'projects' => Project::query()->orderBy('sort_order')->get()
                ->map(fn (Project $project): array => [
                    'id' => $project->id,
                    'slug' => $project->slug,
                    'title' => $project->getTranslation('title', 'fr'),
                    'status' => $project->status->value,
                ])
                ->all(),
            'posts' => Post::query()->latest()->get()
                ->map(fn (Post $post): array => [
                    'id' => $post->id,
                    'slug' => $post->slug,
                    'title' => $post->getTranslation('title', 'fr'),
                    'status' => $post->status->value,
                ])
                ->all(),
            'post_tags' => PostTag::query()->orderBy('slug')->get()->map(fn (PostTag $tag): string => $tag->getTranslation('name', 'fr'))->all(),
            // Chaque série avec ses articles dans l'ordre de lecture, pour placer un nouvel article.
            'post_series' => PostSeries::query()->with(['posts' => fn ($query) => $query->orderBy('series_position')])->get()
                ->map(fn (PostSeries $series): array => [
                    'name' => $series->getTranslation('name', 'fr'),
                    'posts' => $series->posts->map(fn (Post $post): array => [
                        'position' => $post->series_position,
                        'id' => $post->id,
                        'title' => $post->getTranslation('title', 'fr'),
                        'status' => $post->status->value,
                    ])->all(),
                    'next_position' => ($series->posts->max('series_position') ?? 0) + 1,
                ])
                ->all(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}

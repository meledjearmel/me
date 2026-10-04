<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Http\Middleware\SetLocale;
use App\Models\Post;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\UsesItem;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /** @var list<string> */
    private const array STATIC_PATHS = ['', '/about', '/skills', '/projects', '/contact', '/testimonials'];

    public function __invoke(): Response
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        $entries = collect(self::STATIC_PATHS)
            // Page « Uses » sans élément publié : elle renvoie une 404.
            ->when(UsesItem::query()->published()->exists(), fn ($paths) => $paths->push('/uses'))
            ->map(fn (string $path): array => ['path' => $path, 'lastmod' => null]);

        $projects = Project::query()
            ->where('status', ProjectStatus::Published)
            ->orderBy('sort_order')
            ->get(['slug', 'updated_at'])
            ->map(fn (Project $project): array => [
                'path' => "/projects/{$project->slug}",
                'lastmod' => $project->updated_at?->toAtomString(),
            ]);

        // Blog désactivé : ses pages renvoient une 404, elles n'ont rien à faire dans le plan du site.
        $blog = SiteSetting::current()->blog_enabled
            ? Post::query()
                ->published()
                ->latest('published_at')
                ->get(['slug', 'updated_at'])
                ->map(fn (Post $post): array => [
                    'path' => "/blog/{$post->slug}",
                    'lastmod' => $post->updated_at?->toAtomString(),
                ])
                ->prepend(['path' => '/blog', 'lastmod' => null])
            : collect();

        return response()
            ->view('sitemap', [
                'baseUrl' => $baseUrl,
                'locales' => SetLocale::LOCALES,
                'entries' => $entries->concat($projects)->concat($blog),
            ])
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}

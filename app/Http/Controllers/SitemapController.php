<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Http\Middleware\SetLocale;
use App\Models\Project;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /** @var list<string> */
    private const array STATIC_PATHS = ['', '/about', '/skills', '/projects', '/contact', '/testimonials'];

    public function __invoke(): Response
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        $entries = collect(self::STATIC_PATHS)
            ->map(fn (string $path): array => ['path' => $path, 'lastmod' => null]);

        $projects = Project::query()
            ->where('status', ProjectStatus::Published)
            ->orderBy('sort_order')
            ->get(['slug', 'updated_at'])
            ->map(fn (Project $project): array => [
                'path' => "/projects/{$project->slug}",
                'lastmod' => $project->updated_at?->toAtomString(),
            ]);

        return response()
            ->view('sitemap', [
                'baseUrl' => $baseUrl,
                'locales' => SetLocale::LOCALES,
                'entries' => $entries->concat($projects),
            ])
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}

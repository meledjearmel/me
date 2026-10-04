<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Profile;
use App\Models\SiteSetting;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Flux RSS des articles du blog, dans la langue de l'adresse (/fr/blog/feed, /en/blog/feed).
 */
class BlogFeedController extends Controller
{
    private const int LIMIT = 30;

    public function __invoke(string $locale): Response
    {
        abort_unless(SiteSetting::current()->blog_enabled, HttpResponse::HTTP_NOT_FOUND);

        return response()
            ->view('blog-feed', [
                'baseUrl' => rtrim((string) config('app.url'), '/'),
                'locale' => $locale,
                'profile' => Profile::query()->firstOrFail(),
                'posts' => Post::query()->published()->with('tags')->latest('published_at')->limit(self::LIMIT)->get(),
            ])
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }
}

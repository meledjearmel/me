<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Page « Now » : ce sur quoi je travaille en ce moment. Sans texte, elle n'existe pas (404).
 */
class NowController extends Controller
{
    public function __invoke(string $locale): Response
    {
        $settings = SiteSetting::current();
        $text = $settings->nowText($locale);

        abort_if($text === null, HttpResponse::HTTP_NOT_FOUND);

        return Inertia::render('public/now', [
            'text' => $text,
            // Langue réelle du texte : `fr` quand l'anglais n'est pas rédigé.
            'contentLocale' => filled($settings->now_content[$locale] ?? null) ? $locale : 'fr',
            'updatedAt' => $settings->now_updated_at?->toIso8601String(),
        ]);
    }
}

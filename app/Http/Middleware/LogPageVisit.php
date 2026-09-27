<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class LogPageVisit
{
    /** Fenêtre pendant laquelle une même session ne compte qu'une fois pour une même page. */
    private const DEDUPE_MINUTES = 30;

    /**
     * Fragments (insensibles à la casse) d'User-Agent identifiant des robots
     * ou outils, à exclure du compteur de visites.
     *
     * @var list<string>
     */
    private const BOT_USER_AGENT_HINTS = [
        'bot', 'spider', 'crawl', 'slurp', 'facebookexternalhit', 'headlesschrome',
        'curl', 'wget', 'python-requests', 'go-http-client', 'axios', 'okhttp',
        'libwww-perl', 'postman', 'phantomjs',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('get') && ! $this->isBot($request) && ! $this->alreadySeen($request)) {
            PageVisit::query()->create([
                'path' => $request->path(),
                'referrer' => $request->headers->get('referer'),
            ]);
        }

        return $next($request);
    }

    private function isBot(Request $request): bool
    {
        $userAgent = strtolower((string) $request->userAgent());

        if ($userAgent === '') {
            return true;
        }

        foreach (self::BOT_USER_AGENT_HINTS as $hint) {
            if (str_contains($userAgent, $hint)) {
                return true;
            }
        }

        return false;
    }

    /** Marque la page comme vue par cette session ; renvoie true si elle l'était déjà. */
    private function alreadySeen(Request $request): bool
    {
        $key = 'page-visit-seen:'.$request->session()->getId().':'.$request->path();

        return ! Cache::add($key, true, now()->addMinutes(self::DEDUPE_MINUTES));
    }
}

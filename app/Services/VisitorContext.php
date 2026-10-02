<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Ce que l'on peut dire d'un visiteur sans le suivre : robot ou non, type
 * d'appareil, et une empreinte anonyme qui change chaque jour.
 */
class VisitorContext
{
    /**
     * Fragments (insensibles à la casse) d'User-Agent identifiant des robots
     * ou outils, exclus des statistiques.
     *
     * @var list<string>
     */
    private const BOT_USER_AGENT_HINTS = [
        'bot', 'spider', 'crawl', 'slurp', 'facebookexternalhit', 'headlesschrome',
        'curl', 'wget', 'python-requests', 'go-http-client', 'axios', 'okhttp',
        'libwww-perl', 'postman', 'phantomjs',
    ];

    public function isBot(Request $request): bool
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

    /** « mobile », « tablet » ou « desktop », d'après l'User-Agent. */
    public function device(Request $request): string
    {
        $userAgent = strtolower((string) $request->userAgent());

        return match (true) {
            str_contains($userAgent, 'ipad') || str_contains($userAgent, 'tablet')
                || (str_contains($userAgent, 'android') && ! str_contains($userAgent, 'mobile')) => 'tablet',
            str_contains($userAgent, 'mobi') || str_contains($userAgent, 'iphone') => 'mobile',
            default => 'desktop',
        };
    }

    /**
     * Empreinte du visiteur pour la journée : l'IP n'est jamais conservée,
     * et l'empreinte change chaque jour (on ne peut pas suivre quelqu'un).
     */
    public function dailyHash(Request $request): string
    {
        return hash_hmac('sha256', $request->ip().'|'.now()->toDateString(), (string) config('app.key'));
    }
}

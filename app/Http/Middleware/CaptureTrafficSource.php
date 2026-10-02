<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Retient, pour la session, d'où le visiteur est arrivé sur le site : le site
 * d'origine et une éventuelle campagne (utm_source, utm_medium, utm_campaign,
 * ou le raccourci ?ref=linkedin). C'est cette provenance qui est rattachée à
 * un téléchargement du CV, quelle que soit la page d'où il part.
 *
 * La première provenance est gardée ; un lien de campagne explicite la
 * remplace (le visiteur est revenu par un autre canal identifié).
 */
class CaptureTrafficSource
{
    public const SESSION_KEY = 'traffic_source';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')) {
            $campaign = $this->campaign($request);

            if ($campaign['utm_source'] !== null || ! $request->session()->has(self::SESSION_KEY)) {
                $request->session()->put(self::SESSION_KEY, [
                    'referrer_host' => $this->externalReferrerHost($request),
                    ...$campaign,
                ]);
            }
        }

        return $next($request);
    }

    /**
     * Domaine du site d'origine, sans « www. », ou null si le visiteur vient
     * du site lui-même ou d'un accès direct.
     */
    private function externalReferrerHost(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        if (! is_string($host) || $host === '' || $host === $request->getHost()) {
            return null;
        }

        return preg_replace('/^www\./', '', strtolower($host));
    }

    /** @return array{utm_source: string|null, utm_medium: string|null, utm_campaign: string|null} */
    private function campaign(Request $request): array
    {
        $clean = fn (?string $value): ?string => $value === null || trim($value) === ''
            ? null
            : mb_substr(strtolower(trim($value)), 0, 100);

        return [
            'utm_source' => $clean($request->query('utm_source') ?? $request->query('ref')),
            'utm_medium' => $clean($request->query('utm_medium')),
            'utm_campaign' => $clean($request->query('utm_campaign')),
        ];
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /** @var list<string> */
    public const array LOCALES = ['fr', 'en'];

    /**
     * Langue à proposer à un visiteur arrivé sans langue dans l'URL : le
     * français si c'est la langue préférée de son navigateur, l'anglais sinon.
     * Sans préférence déclarée (robots), le français, langue principale du site.
     */
    public static function fromBrowser(Request $request): string
    {
        $preferred = $request->getLanguages()[0] ?? null;

        if ($preferred === null) {
            return 'fr';
        }

        return str_starts_with(strtolower($preferred), 'fr') ? 'fr' : 'en';
    }

    /** Langue de l'URL si elle en porte une, sinon celle du navigateur. */
    public static function fromRequest(Request $request): string
    {
        $segment = $request->segment(1);

        return in_array($segment, self::LOCALES, true) ? $segment : self::fromBrowser($request);
    }

    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale((string) $request->route('locale'));

        return $next($request);
    }
}

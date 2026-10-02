<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use App\Services\VisitorContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class LogPageVisit
{
    /** Fenêtre pendant laquelle une même session ne compte qu'une fois pour une même page. */
    private const DEDUPE_MINUTES = 30;

    public function __construct(private VisitorContext $visitor) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('get') && ! $this->visitor->isBot($request) && ! $this->alreadySeen($request)) {
            PageVisit::query()->create([
                'path' => $request->path(),
                'referrer' => $request->headers->get('referer'),
            ]);
        }

        return $next($request);
    }

    /** Marque la page comme vue par cette session ; renvoie true si elle l'était déjà. */
    private function alreadySeen(Request $request): bool
    {
        $key = 'page-visit-seen:'.$request->session()->getId().':'.$request->path();

        return ! Cache::add($key, true, now()->addMinutes(self::DEDUPE_MINUTES));
    }
}

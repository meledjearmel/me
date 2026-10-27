<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Palettes de la console, reprises de l'app mobile ; la première sert par défaut.
     *
     * @var list<string>
     */
    public const PALETTES = ['gold-graphite', 'sage-amber', 'lime-charcoal', 'sky-amber'];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        View::share('appearance', $request->cookie('appearance') ?? 'system');
        View::share('palette', in_array($request->cookie('palette'), self::PALETTES, true) ? $request->cookie('palette') : self::PALETTES[0]);

        return $next($request);
    }
}

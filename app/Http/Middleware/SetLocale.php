<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /** @var list<string> */
    public const array LOCALES = ['fr', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale((string) $request->route('locale'));

        return $next($request);
    }
}

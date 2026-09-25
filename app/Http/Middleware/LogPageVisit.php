<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogPageVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('get')) {
            PageVisit::query()->create([
                'path' => $request->path(),
                'referrer' => $request->headers->get('referer'),
            ]);
        }

        return $next($request);
    }
}

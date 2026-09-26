<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interdit l'indexation de tout ce qui n'est pas une page publique
 * localisée (admin, connexion, réglages, tableau de bord, erreurs).
 */
class NoIndexPrivateAreas
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->route('locale') === null && ! $request->is('sitemap.xml')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Repli pour les adresses qu'aucune route ne reconnaît. Un lien sans langue
 * (/about, /projects/app-station) est renvoyé vers la même page dans la
 * langue du visiteur ; tout le reste est une vraie 404.
 */
class LocaleRedirectController extends Controller
{
    public function __invoke(Request $request, Router $router): RedirectResponse
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            throw new NotFoundHttpException;
        }

        if (in_array($request->segment(1), SetLocale::LOCALES, true)) {
            throw new NotFoundHttpException;
        }

        $target = '/'.SetLocale::fromBrowser($request).'/'.ltrim($request->path(), '/');

        try {
            $route = $router->getRoutes()->match(Request::create($target));
        } catch (NotFoundHttpException) {
            throw new NotFoundHttpException;
        }

        if ($route->isFallback) {
            throw new NotFoundHttpException;
        }

        $query = $request->getQueryString();

        return redirect($target.($query ? '?'.$query : ''));
    }
}

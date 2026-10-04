<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\NoIndexPrivateAreas;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // La taille du corps est vérifiée dans les groupes, après le démarrage de la session :
        // en middleware global, l'erreur « fichier trop lourd » renvoyée au formulaire était
        // perdue faute de session. Le jeton CSRF arrive par l'en-tête X-XSRF-TOKEN, qui
        // survit au corps vidé par PHP.
        $middleware->remove(ValidatePostSize::class);

        $middleware->web(append: [
            HandleAppearance::class,
            NoIndexPrivateAreas::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            ValidatePostSize::class,
        ]);

        $middleware->api(append: [
            ValidatePostSize::class,
        ]);

        // Désinscription en un clic depuis la messagerie (RFC 8058) : requête sans jeton CSRF.
        $middleware->preventRequestForgery(except: ['*/newsletter/*/unsubscribe']);

        $middleware->alias([
            'locale' => SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Fichier plus lourd que post_max_size : PHP a vidé le corps de la requête.
        // Côté admin, on revient au formulaire avec une erreur lisible plutôt qu'une page 413.
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }

            return back()->withErrors(['video' => __('Fichier trop lourd pour le serveur (:max max).', ['max' => ini_get('post_max_size')])]);
        });
    })->create();

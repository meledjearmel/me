<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * robots.txt généré pour que l'adresse du sitemap suive toujours APP_URL
 * (un fichier statique gardait l'ancien domaine après un changement d'adresse).
 */
class RobotsTxtController extends Controller
{
    public function __invoke(): Response
    {
        return response()
            ->view('robots', ['baseUrl' => rtrim((string) config('app.url'), '/')])
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }
}

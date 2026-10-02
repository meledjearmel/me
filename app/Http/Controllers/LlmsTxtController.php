<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Http\Middleware\SetLocale;
use App\Models\Profile;
use App\Models\Project;
use Illuminate\Http\Response;

/**
 * Résumé en Markdown du portfolio destiné aux moteurs de réponse IA (convention llms.txt).
 */
class LlmsTxtController extends Controller
{
    public function __invoke(): Response
    {
        $locale = SetLocale::LOCALES[0];

        return response()
            ->view('llms', [
                'baseUrl' => rtrim((string) config('app.url'), '/'),
                'locale' => $locale,
                'profile' => Profile::query()->firstOrFail(),
                'projects' => Project::query()
                    ->where('status', ProjectStatus::Published)
                    ->orderBy('sort_order')
                    ->get(['title', 'slug', 'result']),
            ])
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }
}

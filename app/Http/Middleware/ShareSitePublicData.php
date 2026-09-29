<?php

namespace App\Http\Middleware;

use App\Http\Resources\Public\ProfileResource;
use App\Models\Celebration;
use App\Models\JobProfile;
use App\Models\PageVisit;
use App\Models\Profile;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ShareSitePublicData
{
    public function handle(Request $request, Closure $next): Response
    {
        Inertia::share([
            'profile' => fn () => new ProfileResource(Profile::query()->firstOrFail()),
            'siteUrl' => rtrim((string) config('app.url'), '/'),
            // Surprise tirée au sort ; le navigateur décide s'il l'affiche (une fois par session au plus).
            'celebration' => function (): ?array {
                $celebration = Celebration::pickRandom();

                return $celebration === null ? null : [
                    'id' => $celebration->id,
                    'message' => $celebration->getTranslation('message', app()->getLocale()),
                    'buttonLabel' => $celebration->getTranslation('button_label', app()->getLocale()),
                    'total' => $celebration->congratulations_count,
                    'chance' => $celebration->chance_percent / 100,
                    'delaySeconds' => $celebration->delay_seconds,
                    'displaySeconds' => $celebration->display_seconds,
                    'snoozeDays' => $celebration->snooze_days,
                ];
            },
            'visitCount' => fn () => PageVisit::query()->count(),
            // Profils proposés dans la fenêtre « Embauche » : chacun a son CV.
            'cvProfiles' => fn () => JobProfile::query()
                ->published()
                ->orderBy('sort_order')
                ->get()
                ->map(fn (JobProfile $jobProfile): array => [
                    'id' => $jobProfile->id,
                    'label' => $jobProfile->getTranslation('label', app()->getLocale()),
                ])
                ->all(),
        ]);

        return $next($request);
    }
}

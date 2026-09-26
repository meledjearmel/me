<?php

namespace App\Http\Middleware;

use App\Http\Resources\Public\ProfileResource;
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

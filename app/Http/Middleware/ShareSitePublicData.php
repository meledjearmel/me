<?php

namespace App\Http\Middleware;

use App\Http\Resources\Public\ProfileResource;
use App\Models\Celebration;
use App\Models\JobProfile;
use App\Models\PageVisit;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Services\BookingCalendar;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ShareSitePublicData
{
    public function handle(Request $request, Closure $next): Response
    {
        self::share();

        return $next($request);
    }

    /**
     * Données dont la coque publique a besoin (profil, surprise, CV…). Aussi
     * appelée par les pages d'erreur, rendues hors des routes publiques.
     */
    public static function share(): void
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
            // La prise de rendez-vous est ouverte : la page et ses raccourcis s'affichent.
            // Le bouton « Contact » ouvre le tiroir latéral, ou mène à la page Contact.
            'contactOpensDrawer' => fn () => SiteSetting::current()->contact_opens_drawer,
            // Les visiteurs peuvent joindre ou filmer une vidéo avec leur avis.
            'testimonialVideoEnabled' => fn () => SiteSetting::current()->testimonial_video_enabled,
            // Le blog est affiché (navigation, pages).
            'blogEnabled' => fn () => SiteSetting::current()->blog_enabled,
            'bookingOpen' => fn () => app(BookingCalendar::class)->isOpen(),
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
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Middleware\CaptureTrafficSource;
use App\Http\Requests\CvDownloadRequest;
use App\Jobs\SendPushNotification;
use App\Models\CvDownload;
use App\Models\JobProfile;
use App\Models\Profile;
use App\Services\CvGenerator;
use App\Services\GeoLocator;
use App\Services\VisitorContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * Téléchargement du CV depuis le site (page contact) : sert le PDF du profil
 * métier principal dans la langue de la page, et enregistre d'où vient le
 * visiteur. Les robots et les doubles clics du jour ne sont pas comptés.
 */
class CvDownloadController extends Controller
{
    public function __invoke(
        CvDownloadRequest $request,
        CvGenerator $cv,
        GeoLocator $geo,
        VisitorContext $visitor,
    ): Response {
        $jobProfile = self::primaryJobProfile();
        abort_if($jobProfile === null, 404);

        $locale = app()->getLocale();
        $resolved = $cv->resolve($jobProfile, $locale);

        if (! $visitor->isBot($request)) {
            $this->record($request, $jobProfile, $locale, $resolved['source']->value, $geo, $visitor);
        }

        return response($resolved['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$cv->filename($jobProfile).'"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Profil métier dont le CV est proposé : celui choisi dans l'admin, sinon
     * le premier profil publié. Null s'il n'y en a aucun (pas de bouton).
     */
    public static function primaryJobProfile(): ?JobProfile
    {
        $chosen = Profile::query()->value('cv_job_profile_id');

        return ($chosen ? JobProfile::query()->published()->find($chosen) : null)
            ?? JobProfile::query()->published()->orderBy('sort_order')->first();
    }

    private function record(
        CvDownloadRequest $request,
        JobProfile $jobProfile,
        string $locale,
        string $source,
        GeoLocator $geo,
        VisitorContext $visitor,
    ): void {
        $hash = $visitor->dailyHash($request);
        $email = $request->validated('email');

        $alreadyToday = CvDownload::query()
            ->where('visitor_hash', $hash)
            ->where('created_at', '>=', today())
            ->first();

        // Double clic : on ne compte pas deux fois, mais un email laissé au second essai est gardé.
        if ($alreadyToday !== null) {
            if ($email && ! $alreadyToday->email) {
                $alreadyToday->update(['email' => $email]);
            }

            return;
        }

        /** @var array{referrer_host?: string|null, utm_source?: string|null, utm_medium?: string|null, utm_campaign?: string|null} $traffic */
        $traffic = $request->session()->get(CaptureTrafficSource::SESSION_KEY, []);

        $download = CvDownload::query()->create([
            'job_profile_id' => $jobProfile->id,
            'locale' => $locale,
            'source' => $source,
            'email' => $email,
            ...$geo->locate($request->ip()),
            'referrer_host' => $traffic['referrer_host'] ?? null,
            'utm_source' => $traffic['utm_source'] ?? null,
            'utm_medium' => $traffic['utm_medium'] ?? null,
            'utm_campaign' => $traffic['utm_campaign'] ?? null,
            'device' => $visitor->device($request),
            'visitor_hash' => $hash,
        ]);

        $place = collect([$download->city, $download->country])->filter()->implode(', ') ?: 'Lieu inconnu';

        SendPushNotification::dispatch(
            'CV téléchargé',
            collect([$place, 'via '.$download->origin(), $download->email])->filter()->implode(' · '),
            ['type' => 'cv_download', 'id' => (string) $download->id],
        );
    }
}

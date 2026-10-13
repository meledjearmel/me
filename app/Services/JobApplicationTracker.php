<?php

namespace App\Services;

use App\Http\Middleware\CaptureTrafficSource;
use App\Jobs\SendPushNotification;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use Illuminate\Http\Request;

/**
 * Enregistre ce qui se passe sur une page de candidature : une consultation par visiteur et
 * par jour, chaque téléchargement du CV. Les robots et mes propres visites (connecté à
 * l'admin) ne comptent pas. Une notification part à la première ouverture et à chaque CV.
 */
class JobApplicationTracker
{
    public function __construct(
        private readonly GeoLocator $geo,
        private readonly VisitorContext $visitor,
    ) {}

    public function view(JobApplication $application, Request $request): void
    {
        if (! $this->counts($request)) {
            return;
        }

        $hash = $this->visitor->dailyHash($request);
        $alreadyToday = $application->events()
            ->where('type', JobApplicationEvent::VIEW)
            ->where('visitor_hash', $hash)
            ->where('created_at', '>=', today())
            ->exists();

        if ($alreadyToday) {
            return;
        }

        $event = $this->record($application, $request, JobApplicationEvent::VIEW, $hash);
        $isFirst = $application->first_viewed_at === null;

        $application->forceFill([
            'views_count' => $application->views_count + 1,
            'first_viewed_at' => $application->first_viewed_at ?? now(),
            'last_viewed_at' => now(),
        ])->save();

        SendPushNotification::dispatch(
            $isFirst ? "{$application->company} a ouvert ta candidature" : "Candidature {$application->company} consultée",
            collect([$application->position, $this->place($event)])->filter()->implode(' · '),
            ['type' => 'job_application', 'id' => (string) $application->id],
        );
    }

    public function cvDownload(JobApplication $application, Request $request): void
    {
        if (! $this->counts($request)) {
            return;
        }

        $event = $this->record($application, $request, JobApplicationEvent::CV_DOWNLOAD, $this->visitor->dailyHash($request));
        $application->increment('cv_downloads_count');

        SendPushNotification::dispatch(
            "CV téléchargé par {$application->company}",
            collect([$application->position, $this->place($event)])->filter()->implode(' · '),
            ['type' => 'job_application', 'id' => (string) $application->id],
        );
    }

    private function counts(Request $request): bool
    {
        return ! $this->visitor->isBot($request) && $request->user() === null;
    }

    private function record(JobApplication $application, Request $request, string $type, string $hash): JobApplicationEvent
    {
        /** @var array{referrer_host?: string|null} $traffic */
        $traffic = $request->hasSession() ? $request->session()->get(CaptureTrafficSource::SESSION_KEY, []) : [];

        return $application->events()->create([
            'type' => $type,
            ...$this->geo->locate($request->ip()),
            'device' => $this->visitor->device($request),
            'referrer_host' => $traffic['referrer_host'] ?? null,
            'visitor_hash' => $hash,
        ]);
    }

    private function place(JobApplicationEvent $event): ?string
    {
        return collect([$event->city, $event->country])->filter()->implode(', ') ?: null;
    }
}

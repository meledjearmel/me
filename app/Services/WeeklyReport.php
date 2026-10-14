<?php

namespace App\Services;

use App\Ai\Agents\WeeklyDigestWriter;
use App\Enums\AppointmentStatus;
use App\Enums\EngagementType;
use App\Enums\PublicationStatus;
use App\Mail\WeeklyReportMail;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Engagement;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use App\Models\PageVisit;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostReaction;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Models\Subscriber;
use App\Services\Ai\TextAssistService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Le rapport de la semaine, envoyé chaque lundi : l'audience des 7 derniers jours comparée à
 * la semaine d'avant, les demandes et estimations reçues (avec l'avis de l'IA), les pages de
 * candidature consultées, le blog, ce qui attend une action, et « À retenir » rédigé par l'IA.
 * Partagé par le mail, l'aperçu de l'administration, l'API et le serveur MCP.
 */
class WeeklyReport
{
    private const int DAYS = 7;

    public function __construct(
        private readonly DashboardReport $dashboard,
        private readonly TextAssistService $assist,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(bool $withHighlights = true): array
    {
        $now = Carbon::now();
        $since = $now->copy()->subDays(self::DAYS);
        $before = $since->copy()->subDays(self::DAYS);
        $dashboard = $this->dashboard->toArray(self::DAYS);

        $report = [
            'period' => ['from' => $since->toDateString(), 'to' => $now->toDateString()],
            'audience' => $this->audience($dashboard['visits'], $since, $before),
            'requests' => $this->requests($since),
            // L'estimateur de la semaine : de la visite de sa page à la demande, et les montants.
            'estimator' => collect($dashboard['estimator'])->only(['page_visitors', 'requests', 'conversion_rate', 'by_currency', 'ai_gap'])->all(),
            'applications' => $this->applications($since),
            'cv_downloads' => $dashboard['cv_downloads']['period'] + JobApplicationEvent::query()
                ->where('type', JobApplicationEvent::CV_DOWNLOAD)
                ->where('created_at', '>=', $since)
                ->count(),
            'blog' => [
                'published' => Post::query()->where('status', PublicationStatus::Published)->where('published_at', '>=', $since)->count(),
                'comments' => PostComment::query()->where('created_at', '>=', $since)->count(),
                'reactions' => PostReaction::query()->where('created_at', '>=', $since)->count(),
                'new_subscribers' => Subscriber::query()->active()->where('confirmed_at', '>=', $since)->count(),
                'subscribers' => Subscriber::query()->active()->count(),
            ],
            // Seulement ce qui attend vraiment : les lignes à zéro n'encombrent pas le mail.
            'todo' => array_filter($dashboard['todo'], fn (int $count): bool => $count > 0),
            'highlights' => null,
        ];

        if ($withHighlights) {
            $report['highlights'] = $this->highlights($report);
        }

        return $report;
    }

    /**
     * Envoie le rapport à l'email du profil. Sans $force (envoi planifié), rien ne part si le
     * rapport est désactivé, déjà envoyé dans les 6 derniers jours ou si la semaine est vide.
     *
     * @return 'sent'|'disabled'|'already_sent'|'empty'|'no_recipient'
     */
    public function send(bool $force = false): string
    {
        $settings = SiteSetting::current();
        $recipient = Profile::query()->value('email');

        if (! $force && ! $settings->weekly_report_enabled) {
            return 'disabled';
        }

        if (! $force && $settings->weekly_report_sent_at?->gt(Carbon::now()->subDays(6))) {
            return 'already_sent';
        }

        if (blank($recipient)) {
            return 'no_recipient';
        }

        $report = $this->build(withHighlights: false);

        if (! $force && ! $this->isWorthSending($report)) {
            return 'empty';
        }

        $report['highlights'] = $this->highlights($report);

        Mail::to($recipient)->send(new WeeklyReportMail($report));
        $settings->forceFill(['weekly_report_sent_at' => Carbon::now()])->save();

        return 'sent';
    }

    /**
     * Le rapport a-t-il quelque chose à dire ? Une semaine sans visite ni demande n'envoie rien.
     *
     * @param  array<string, mixed>  $report
     */
    public function isWorthSending(array $report): bool
    {
        return $report['audience']['visits'] > 0
            || $report['requests']['total'] > 0
            || $report['applications']['viewed'] !== []
            || $report['todo'] !== [];
    }

    /**
     * @param  array<string, mixed>  $visits  Bloc `visits` du tableau de bord, sur 7 jours.
     * @return array{visits: int, visitors: int, previous_visits: int, previous_visitors: int, change: int|null, top_content: list<array<string, mixed>>, sources: list<array{label: string, count: int}>}
     */
    private function audience(array $visits, Carbon $since, Carbon $before): array
    {
        $previous = PageVisit::query()->where('created_at', '>=', $before)->where('created_at', '<', $since);
        $previousVisits = (clone $previous)->count();

        return [
            'visits' => $visits['period'],
            'visitors' => $visits['visitors'],
            'previous_visits' => $previousVisits,
            'previous_visitors' => (clone $previous)->whereNotNull('visitor_hash')->distinct()->count('visitor_hash'),
            // Évolution des visites en %, null sans point de comparaison.
            'change' => $previousVisits > 0 ? (int) round(($visits['period'] - $previousVisits) / $previousVisits * 100) : null,
            'top_content' => array_slice($visits['top_content'], 0, 5),
            'sources' => array_slice($visits['by_source'], 0, 4),
        ];
    }

    /**
     * @return array{total: int, contacts: int, freelance: int, hiring: int, appointments: int, upcoming_appointments: int, estimates: list<array{id: int, subject: string|null, estimate: string|null, ai_billing: string|null, ai_hours: string|null}>}
     */
    private function requests(Carbon $since): array
    {
        $engagements = Engagement::query()->where('created_at', '>=', $since)->latest()->get();
        $contacts = Contact::query()->where('created_at', '>=', $since)->count();
        $appointments = Appointment::query()->where('created_at', '>=', $since)->count();

        return [
            'total' => $contacts + $engagements->count() + $appointments,
            'contacts' => $contacts,
            'freelance' => $engagements->where('type', EngagementType::Freelance)->count(),
            'hiring' => $engagements->where('type', EngagementType::Hiring)->count(),
            'appointments' => $appointments,
            'upcoming_appointments' => Appointment::query()
                ->where('status', AppointmentStatus::Confirmed)
                ->whereBetween('starts_at', [Carbon::now(), Carbon::now()->addDays(self::DAYS)])
                ->count(),
            // Les demandes passées par l'estimateur, avec ce que l'IA en pense.
            'estimates' => $engagements
                ->filter(fn (Engagement $engagement): bool => $engagement->estimate !== null)
                ->take(5)
                ->map(fn (Engagement $engagement): array => [
                    'id' => $engagement->id,
                    'subject' => $engagement->subject,
                    'estimate' => $engagement->estimate_label,
                    'ai_billing' => $engagement->ai_analysis['billing'] ?? null,
                    'ai_hours' => isset($engagement->ai_analysis['hours_min'])
                        ? "{$engagement->ai_analysis['hours_min']} – {$engagement->ai_analysis['hours_max']} h"
                        : null,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Les pages de candidature ouvertes cette semaine, et celles créées.
     *
     * @return array{created: int, viewed: list<array{id: int, company: string, position: string, views: int, cv_downloads: int, first_time: bool}>}
     */
    private function applications(Carbon $since): array
    {
        $events = JobApplicationEvent::query()
            ->where('created_at', '>=', $since)
            ->get(['job_application_id', 'type'])
            ->groupBy('job_application_id');

        $viewed = JobApplication::query()
            ->whereIn('id', $events->keys())
            ->get()
            ->map(fn (JobApplication $application): array => [
                'id' => $application->id,
                'company' => $application->company,
                'position' => $application->position,
                'views' => $events[$application->id]->where('type', JobApplicationEvent::VIEW)->count(),
                'cv_downloads' => $events[$application->id]->where('type', JobApplicationEvent::CV_DOWNLOAD)->count(),
                // Ouverte pour la première fois cette semaine.
                'first_time' => $application->first_viewed_at?->gte($since) ?? false,
            ])
            ->sortByDesc(fn (array $row): int => $row['views'] + $row['cv_downloads'] * 3)
            ->values()
            ->all();

        return [
            'created' => JobApplication::query()->where('created_at', '>=', $since)->count(),
            'viewed' => $viewed,
        ];
    }

    /**
     * Trois points à retenir, rédigés par l'IA d'après les chiffres ; null si aucun
     * fournisseur ne répond (le rapport part quand même, sans cette partie).
     *
     * @param  array<string, mixed>  $report
     * @return list<string>|null
     */
    private function highlights(array $report): ?array
    {
        if (! $this->isWorthSending($report)) {
            return null;
        }

        $result = $this->assist->promptJson(
            new WeeklyDigestWriter,
            (string) json_encode(collect($report)->except('highlights')->all(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            function (array $data): ?array {
                $points = collect(is_array($data['highlights'] ?? null) ? $data['highlights'] : [])
                    ->map(fn (mixed $point): ?string => is_string($point) && trim($point) !== '' ? mb_substr(trim($point), 0, 300) : null)
                    ->filter()
                    ->take(3)
                    ->values()
                    ->all();

                return $points === [] ? null : ['highlights' => $points];
            },
            'ai.text_assist',
        );

        return $result['highlights'] ?? null;
    }
}

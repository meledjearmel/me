<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\ContactStatus;
use App\Enums\EngagementStatus;
use App\Enums\EngagementType;
use App\Enums\ProjectStatus;
use App\Enums\TestimonialStatus;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Counter;
use App\Models\CvDownload;
use App\Models\Domain;
use App\Models\Education;
use App\Models\Engagement;
use App\Models\Experience;
use App\Models\PageVisit;
use App\Models\Post;
use App\Models\ProfessionalReference;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Models\Technology;
use App\Models\TechnologyCategory;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tout ce que le site sait sur lui-même, regroupé en quatre blocs (à traiter,
 * audience, contenu, santé du contenu). Partagé par le tableau de bord web et l'API.
 */
class DashboardReport
{
    /** Période par défaut, en jours. */
    public const int DEFAULT_DAYS = 30;

    /** Périodes proposées, en jours ; `all` remonte à la toute première donnée. */
    public const array PERIODS = ['7', '30', '90', '365', 'all'];

    /** Types de contenu par lesquels filtrer `visits.top_content`. */
    public const array CONTENT_TYPES = ['post', 'project'];

    /** Durée de la période en jours, `null` depuis la toute première donnée. */
    private ?int $days = self::DEFAULT_DAYS;

    /** Type de contenu gardé dans `visits.top_content`, `null` pour les deux. */
    private ?string $contentType = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(?int $days = self::DEFAULT_DAYS, ?string $contentType = null): array
    {
        $this->days = $days;
        $this->contentType = $contentType;

        return [
            'todo' => $this->todo(),
            'visits' => $this->visits(),
            'content' => $this->content(),
            'distribution' => $this->distribution(),
            'health' => $this->health(),
            'recent' => $this->recent(),
            'cv_downloads' => $this->cvDownloads(),
            'conversions' => $this->conversions(),
        ];
    }

    /**
     * Téléchargements du CV : volumes, et d'où viennent ceux de la période
     * (pays, provenance : campagne, sinon site d'origine, sinon direct).
     *
     * @return array{
     *     total: int,
     *     period_days: int|null,
     *     since: string|null,
     *     period: int,
     *     with_email: int,
     *     by_country: list<array{label: string, count: int}>,
     *     by_origin: list<array{label: string, count: int}>,
     * }
     */
    private function cvDownloads(): array
    {
        $since = $this->days === null ? null : Carbon::now()->subDays($this->days);
        $start = $since ?? $this->dayOf(CvDownload::query()->min('created_at'));

        return [
            'total' => CvDownload::query()->count(),
            'period_days' => $this->days,
            'since' => $start?->toDateString(),
            'period' => $this->inPeriod(CvDownload::query(), $since)->count(),
            'with_email' => CvDownload::query()->whereNotNull('email')->count(),
            /** Les 5 premiers pays sur la période (« Lieu inconnu » sans localisation). */
            'by_country' => $this->topCvDownloadsBy("coalesce(country, 'Lieu inconnu')", $since),
            /** Les 5 premières provenances sur la période : campagne, sinon site d'origine, sinon `direct`. */
            'by_origin' => $this->topCvDownloadsBy("coalesce(utm_source, referrer_host, 'direct')", $since),
        ];
    }

    /**
     * Les 5 valeurs les plus fréquentes d'une expression SQL parmi les
     * téléchargements du CV de la période.
     *
     * @return list<array{label: string, count: int}>
     */
    private function topCvDownloadsBy(string $expression, ?Carbon $since): array
    {
        return $this->inPeriod(CvDownload::query(), $since)
            ->selectRaw("{$expression} as label, count(*) as count")
            ->groupBy('label')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn (CvDownload $row): array => ['label' => (string) $row->getAttribute('label'), 'count' => (int) $row->getAttribute('count')])
            ->values()
            ->all();
    }

    /**
     * Limite une requête à la période, ou la laisse entière sans début de période.
     *
     * @template TBuilder of Builder
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    private function inPeriod(Builder $query, ?Carbon $since): Builder
    {
        return $query->when($since, fn (Builder $query) => $query->where('created_at', '>=', $since));
    }

    /**
     * Début de la période des visites, `null` depuis la toute première visite.
     */
    private function visitsSince(): ?Carbon
    {
        return $this->days === null ? null : Carbon::today()->subDays($this->days - 1);
    }

    /**
     * Le jour d'une date brute lue en base, `null` sans donnée.
     */
    private function dayOf(mixed $value): ?Carbon
    {
        return $value === null ? null : Carbon::parse($value)->startOfDay();
    }

    /**
     * Ce qui attend une action de ma part.
     *
     * @return array<string, int>
     */
    private function todo(): array
    {
        return [
            'contacts' => Contact::query()->where('status', ContactStatus::New)->count(),
            'engagements' => Engagement::query()->where('status', EngagementStatus::New)->count(),
            'testimonials' => Testimonial::query()->where('status', TestimonialStatus::Pending)->count(),
            'appointments' => Appointment::query()->where('status', AppointmentStatus::Pending)->count(),
        ];
    }

    /**
     * Audience : visites sur la période, courbe, pages les plus vues.
     *
     * @return array<string, mixed>
     */
    private function visits(): array
    {
        $since = $this->visitsSince();
        $start = $since ?? $this->dayOf(PageVisit::query()->min('created_at'));
        [$granularity, $daily] = $this->visitCurve($start);

        $topPages = $this->inPeriod(PageVisit::query(), $since)
            ->select('path', DB::raw('count(*) as total'))
            ->groupBy('path')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn (PageVisit $visit): array => ['path' => '/'.ltrim($visit->path, '/'), 'count' => (int) $visit->total])
            ->all();

        $inPeriod = $this->inPeriod(PageVisit::query(), $since);

        return [
            'total' => PageVisit::query()->count(),
            'period_days' => $this->days,
            'since' => $start?->toDateString(),
            'period' => (clone $inPeriod)->count(),
            'today' => PageVisit::query()->where('created_at', '>=', Carbon::today())->count(),
            'french' => (clone $inPeriod)->where('path', 'like', 'fr%')->count(),
            'english' => (clone $inPeriod)->where('path', 'like', 'en%')->count(),
            'visitors' => (clone $inPeriod)->whereNotNull('visitor_hash')->distinct()->count('visitor_hash'),
            /** @var 'day'|'month'|'year' Pas de la courbe `daily`. */
            'granularity' => $granularity,
            'daily' => $daily,
            /** Visiteurs uniques par provenance (campagne, sinon site d'origine, sinon `direct`). */
            'by_source' => $this->topVisitorsBy("coalesce(source, 'direct')", $since),
            /** Visiteurs uniques par type d'appareil. */
            'by_device' => $this->topVisitorsBy("coalesce(device, 'inconnu')", $since),
            /** @var list<array{path: string, count: int}> */
            'top_pages' => $topPages,
            /**
             * Articles et projets les plus vus, toutes langues réunies, avec leur titre, leurs
             * visiteurs uniques et leur principale provenance.
             */
            'top_content' => $this->topContent($since),
        ];
    }

    /**
     * La courbe des visites, sans trou : un point par jour jusqu'à 90 jours, par mois
     * au-delà (les 12 derniers mois sur 365 jours), par an quand toute la période
     * dépasse 3 ans.
     *
     * @return array{0: 'day'|'month'|'year', 1: list<array{date: string, count: int}>}
     */
    private function visitCurve(?Carbon $start): array
    {
        $today = Carbon::today();

        [$granularity, $from] = match (true) {
            $start === null => ['day', null],
            $this->days !== null && $this->days <= 90 => ['day', $start->copy()],
            $this->days !== null => ['month', $today->copy()->startOfMonth()->subMonths(11)],
            $start->diffInYears($today) > 3 => ['year', $start->copy()->startOfYear()],
            default => ['month', $start->copy()->startOfMonth()],
        };

        if ($from === null) {
            return [$granularity, []];
        }

        $format = ['day' => 'Y-m-d', 'month' => 'Y-m-01', 'year' => 'Y-01-01'][$granularity];

        $perBucket = PageVisit::query()
            ->where('created_at', '>=', $from)
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day')
            ->reduce(function (array $buckets, mixed $total, string $day) use ($format): array {
                $key = Carbon::parse($day)->format($format);
                $buckets[$key] = ($buckets[$key] ?? 0) + (int) $total;

                return $buckets;
            }, []);

        $points = [];

        for ($date = $from->copy(); $date->lte($today); $date->add(1, $granularity)) {
            $key = $date->format($format);
            $points[] = ['date' => $key, 'count' => $perBucket[$key] ?? 0];
        }

        return [$granularity, $points];
    }

    /**
     * Les visites des pages d'article (`blog/<slug>`) et de projet (`projects/<slug>`),
     * regroupées par contenu quelle que soit la langue, éventuellement d'un seul type.
     *
     * @return list<array{type: string, title: string, url: string, visits: int, visitors: int, top_source: string}>
     */
    private function topContent(?Carbon $since): array
    {
        $groups = $this->inPeriod(PageVisit::query(), $since)
            ->where(fn ($query) => $query->where('path', 'like', '%/blog/%')->orWhere('path', 'like', '%/projects/%'))
            ->get(['path', 'visitor_hash', 'source'])
            ->map(function (PageVisit $visit): ?array {
                if (! preg_match('~^[a-z]{2}/(blog|projects)/([^/]+)$~', ltrim($visit->path, '/'), $matches) || $matches[2] === 'feed') {
                    return null;
                }

                return ['type' => $matches[1] === 'blog' ? 'post' : 'project', 'slug' => $matches[2], 'visit' => $visit];
            })
            ->filter(fn (?array $row): bool => $row !== null && ($this->contentType === null || $row['type'] === $this->contentType))
            ->groupBy(fn (array $row): string => "{$row['type']}:{$row['slug']}")
            ->sortByDesc(fn ($rows) => $rows->count())
            ->take(8);

        $slugs = $groups->map(fn ($rows) => $rows->first())->groupBy('type')->map(fn ($rows) => $rows->pluck('slug'));
        $titles = [
            'post' => Post::query()->whereIn('slug', $slugs->get('post', collect()))->get(['slug', 'title'])->mapWithKeys(fn (Post $post): array => [$post->slug => $post->getTranslation('title', 'fr')]),
            'project' => Project::query()->whereIn('slug', $slugs->get('project', collect()))->get(['slug', 'title'])->mapWithKeys(fn (Project $project): array => [$project->slug => $project->getTranslation('title', 'fr')]),
        ];

        return $groups
            ->map(function ($rows) use ($titles): array {
                ['type' => $type, 'slug' => $slug] = $rows->first();
                $visits = $rows->pluck('visit');

                return [
                    'type' => $type,
                    // Un contenu supprimé ou renommé depuis garde son slug comme titre.
                    'title' => $titles[$type][$slug] ?? $slug,
                    'url' => '/fr/'.($type === 'post' ? 'blog' : 'projects')."/{$slug}",
                    'visits' => $visits->count(),
                    'visitors' => $visits->pluck('visitor_hash')->filter()->unique()->count(),
                    'top_source' => (string) $visits->countBy(fn (PageVisit $visit): string => $visit->source ?? 'direct')->sortDesc()->keys()->first(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Visiteurs uniques (empreinte du jour) regroupés par une expression SQL,
     * les 6 plus fréquents.
     *
     * @return list<array{label: string, count: int}>
     */
    private function topVisitorsBy(string $expression, ?Carbon $since): array
    {
        return $this->inPeriod(PageVisit::query(), $since)
            ->whereNotNull('visitor_hash')
            ->selectRaw("{$expression} as label, count(distinct visitor_hash) as count")
            ->groupBy('label')
            ->orderByDesc('count')
            ->limit(6)
            ->get()
            ->map(fn (PageVisit $row): array => ['label' => (string) $row->getAttribute('label'), 'count' => (int) $row->getAttribute('count')])
            ->values()
            ->all();
    }

    /**
     * Ce que les visiteurs font sur la période : CV, messages, collaborations,
     * rendez-vous, et la part des visiteurs uniques que cela représente.
     *
     * @return array{period_days: int|null, since: string|null, visitors: int, goals: list<array{key: string, count: int, rate: float}>}
     */
    private function conversions(): array
    {
        $since = $this->visitsSince();
        $start = $since ?? $this->dayOf(PageVisit::query()->min('created_at'));
        $visitors = $this->inPeriod(PageVisit::query(), $since)
            ->whereNotNull('visitor_hash')
            ->distinct()
            ->count('visitor_hash');

        $counts = [
            'cv_downloads' => $this->inPeriod(CvDownload::query(), $since)->count(),
            'contacts' => $this->inPeriod(Contact::query(), $since)->count(),
            'engagements' => $this->inPeriod(Engagement::query(), $since)->count(),
            'appointments' => $this->inPeriod(Appointment::query(), $since)->count(),
        ];

        return [
            'period_days' => $this->days,
            'since' => $start?->toDateString(),
            'visitors' => $visitors,
            'goals' => collect($counts)
                ->map(fn (int $count, string $key): array => [
                    'key' => $key,
                    'count' => $count,
                    'rate' => $visitors > 0 ? round($count / $visitors * 100, 1) : 0.0,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Ce que contient le site.
     *
     * @return array<string, mixed>
     */
    private function content(): array
    {
        $firstMission = Experience::query()->min('start_date');

        return [
            'projects' => [
                'published' => Project::query()->where('status', ProjectStatus::Published)->count(),
                'archived' => Project::query()->where('status', ProjectStatus::Archived)->count(),
                'featured' => Project::query()->where('is_featured', true)->count(),
                'open_source' => Project::query()->where('is_open_source', true)->count(),
            ],
            'skills' => Skill::query()->count(),
            'technologies' => Technology::query()->count(),
            'domains' => Domain::query()->count(),
            'experiences' => Experience::query()->count(),
            'educations' => Education::query()->count(),
            'references_on_cv' => ProfessionalReference::query()->where('is_public', true)->count(),
            'years_of_experience' => $firstMission === null
                ? 0
                : (int) round(abs(Carbon::parse($firstMission)->diffInMonths(now())) / 12),
            'testimonials' => [
                'approved' => Testimonial::query()->where('status', TestimonialStatus::Approved)->count(),
                'pending' => Testimonial::query()->where('status', TestimonialStatus::Pending)->count(),
                'rejected' => Testimonial::query()->where('status', TestimonialStatus::Rejected)->count(),
                'featured' => Testimonial::query()->where('is_featured', true)->count(),
            ],
            'contacts' => Contact::query()->count(),
            'engagements' => [
                'freelance' => Engagement::query()->where('type', EngagementType::Freelance)->count(),
                'hiring' => Engagement::query()->where('type', EngagementType::Hiring)->count(),
                'cv_sent' => Engagement::query()->whereNotNull('cv_sent_at')->count(),
            ],
            'congratulations' => Counter::total(Counter::CONGRATULATIONS),
        ];
    }

    /**
     * Répartition du contenu : projets et compétences par domaine, technologies par catégorie.
     *
     * @return array<string, mixed>
     */
    private function distribution(): array
    {
        $domains = Domain::query()
            ->withCount(['projects', 'skills'])
            ->orderBy('sort_order')
            ->get();

        $label = fn (Domain $domain): string => $domain->getTranslation('label', 'fr');

        return [
            /** @var list<array{label: string, color: string|null, count: int}> */
            'projects_by_domain' => $domains->map(fn (Domain $domain): array => [
                'label' => $label($domain), 'color' => $domain->color, 'count' => $domain->projects_count,
            ])->filter(fn (array $row): bool => $row['count'] > 0)->values()->all(),
            /** @var list<array{label: string, color: string|null, count: int}> */
            'skills_by_domain' => $domains->map(fn (Domain $domain): array => [
                'label' => $label($domain), 'color' => $domain->color, 'count' => $domain->skills_count,
            ])->filter(fn (array $row): bool => $row['count'] > 0)->values()->all(),
            'technologies_by_category' => TechnologyCategory::query()
                ->withCount('technologies')
                ->get()
                ->map(fn (TechnologyCategory $category): array => [
                    'label' => $category->getTranslation('label', 'fr'),
                    'count' => $category->technologies_count,
                ])
                ->filter(fn (array $row): bool => $row['count'] > 0)
                ->sortByDesc('count')
                ->values()
                ->all(),
        ];
    }

    /**
     * Ce qu'il reste à compléter pour que le site et les CV soient complets.
     *
     * @return list<array{key: string, ok: bool, count: int}>
     */
    private function health(): array
    {
        $profile = Profile::query()->first();
        $withoutCover = Project::query()
            ->where('status', ProjectStatus::Published)
            ->get()
            ->filter(fn (Project $project): bool => $project->getFirstMedia('cover') === null)
            ->count();

        return [
            ['key' => 'profile_photo', 'ok' => $profile?->getFirstMedia('photo') !== null, 'count' => 0],
            ['key' => 'cv_photo', 'ok' => $profile?->getFirstMedia('cv_photo') !== null, 'count' => 0],
            ['key' => 'cv_identity', 'ok' => filled($profile?->cv_last_name) && filled($profile?->cv_first_name), 'count' => 0],
            ['key' => 'cv_references', 'ok' => ProfessionalReference::query()->where('is_public', true)->exists(), 'count' => 0],
            ['key' => 'project_covers', 'ok' => $withoutCover === 0, 'count' => $withoutCover],
            ['key' => 'featured_testimonials', 'ok' => Testimonial::query()->where('is_featured', true)->exists(), 'count' => 0],
        ];
    }

    /**
     * Les derniers éléments reçus, à traiter en priorité.
     *
     * @return array<string, mixed>
     */
    private function recent(): array
    {
        return [
            'contacts' => Contact::query()->latest()->limit(5)->get()
                ->map(fn (Contact $contact): array => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'subject' => $contact->subject,
                    'is_new' => $contact->status === ContactStatus::New,
                    'at' => $contact->created_at->toIso8601String(),
                ])->all(),
            /** @var list<array{id: int, name: string, company: string|null, type: 'freelance'|'hiring', subject: string|null, is_new: bool, at: string}> */
            'engagements' => Engagement::query()->latest()->limit(5)->get()
                ->map(fn (Engagement $engagement): array => [
                    'id' => $engagement->id,
                    'name' => $engagement->name,
                    'company' => $engagement->company,
                    'type' => $engagement->type->value,
                    'subject' => $engagement->subject,
                    'is_new' => $engagement->status === EngagementStatus::New,
                    'at' => $engagement->created_at->toIso8601String(),
                ])->all(),
            /** @var list<array{id: int, name: string, excerpt: string, at: string}> */
            'testimonials' => Testimonial::query()->where('status', TestimonialStatus::Pending)->latest('submitted_at')->limit(5)->get()
                ->map(fn (Testimonial $testimonial): array => [
                    'id' => $testimonial->id,
                    'name' => $testimonial->author_name,
                    'excerpt' => str((string) collect($testimonial->getTranslations('content'))->first())->limit(90)->toString(),
                    'at' => $testimonial->submitted_at->toIso8601String(),
                ])->all(),
        ];
    }
}

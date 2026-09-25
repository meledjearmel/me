<?php

namespace App\Services;

use App\Enums\ContactStatus;
use App\Enums\EngagementStatus;
use App\Enums\EngagementType;
use App\Enums\ProjectStatus;
use App\Enums\TestimonialStatus;
use App\Models\Contact;
use App\Models\Counter;
use App\Models\Domain;
use App\Models\Education;
use App\Models\Engagement;
use App\Models\Experience;
use App\Models\PageVisit;
use App\Models\ProfessionalReference;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Models\Technology;
use App\Models\Testimonial;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tout ce que le site sait sur lui-même, regroupé en quatre blocs (à traiter,
 * audience, contenu, santé du contenu). Partagé par le tableau de bord web et l'API.
 */
class DashboardReport
{
    /** Nombre de jours affichés dans la courbe d'audience. */
    private const int VISIT_DAYS = 30;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'todo' => $this->todo(),
            'visits' => $this->visits(),
            'content' => $this->content(),
            'distribution' => $this->distribution(),
            'health' => $this->health(),
            'recent' => $this->recent(),
        ];
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
        ];
    }

    /**
     * Audience : visites sur la période, courbe quotidienne, pages les plus vues.
     *
     * @return array<string, mixed>
     */
    private function visits(): array
    {
        $since = Carbon::today()->subDays(self::VISIT_DAYS - 1);

        $perDay = PageVisit::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $daily = collect(range(0, self::VISIT_DAYS - 1))
            ->map(function (int $offset) use ($since, $perDay): array {
                $date = $since->copy()->addDays($offset)->toDateString();

                return ['date' => $date, 'count' => (int) ($perDay[$date] ?? 0)];
            })
            ->all();

        $topPages = PageVisit::query()
            ->where('created_at', '>=', $since)
            ->select('path', DB::raw('count(*) as total'))
            ->groupBy('path')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn (PageVisit $visit): array => ['path' => '/'.ltrim($visit->path, '/'), 'count' => (int) $visit->total])
            ->all();

        $inPeriod = PageVisit::query()->where('created_at', '>=', $since);

        return [
            'total' => PageVisit::query()->count(),
            'period_days' => self::VISIT_DAYS,
            'period' => (clone $inPeriod)->count(),
            'today' => PageVisit::query()->where('created_at', '>=', Carbon::today())->count(),
            'french' => (clone $inPeriod)->where('path', 'like', 'fr%')->count(),
            'english' => (clone $inPeriod)->where('path', 'like', 'en%')->count(),
            'daily' => $daily,
            'top_pages' => $topPages,
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
            'projects_by_domain' => $domains->map(fn (Domain $domain): array => [
                'label' => $label($domain), 'color' => $domain->color, 'count' => $domain->projects_count,
            ])->filter(fn (array $row): bool => $row['count'] > 0)->values()->all(),
            'skills_by_domain' => $domains->map(fn (Domain $domain): array => [
                'label' => $label($domain), 'color' => $domain->color, 'count' => $domain->skills_count,
            ])->filter(fn (array $row): bool => $row['count'] > 0)->values()->all(),
            'technologies_by_category' => Technology::query()
                ->select('category', DB::raw('count(*) as total'))
                ->groupBy('category')
                ->orderByDesc('total')
                ->get()
                ->map(fn (Technology $technology): array => [
                    'category' => $technology->category->value,
                    'count' => (int) $technology->total,
                ])
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

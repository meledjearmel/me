<?php

namespace App\Services;

use App\Enums\AvailabilityStatus;
use App\Enums\EstimatorBilling;
use App\Enums\ProjectStatus;
use App\Enums\ServicePriceMode;
use App\Enums\TestimonialStatus;
use App\Models\AppointmentType;
use App\Models\Certification;
use App\Models\Distinction;
use App\Models\Domain;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\UsesItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Rassemble, en texte, tout ce que le site publie sur le propriétaire (profil,
 * disponibilité, expériences, formation, compétences, projets, offres, avis…)
 * pour servir de connaissance à
 * l'assistant de discussion. Seules les données publiques y figurent : rien de
 * ce qui n'est pas affiché sur le site ne doit pouvoir sortir du bot.
 */
class PortfolioKnowledge
{
    /** Durée de conservation du texte construit, en secondes. */
    private const CACHE_TTL = 600;

    /** Nombre maximal de projets décrits. */
    private const PROJECT_LIMIT = 12;

    /** Nombre maximal d'articles du blog listés, les plus récents. */
    private const POST_LIMIT = 15;

    /** Nombre maximal d'avis cités. */
    private const TESTIMONIAL_LIMIT = 8;

    public function __construct(
        private readonly ProjectEstimator $estimator,
        private readonly BookingCalendar $calendar,
    ) {}

    public static function cacheKey(string $locale): string
    {
        return "portfolio-knowledge.{$locale}";
    }

    public function forLocale(string $locale): string
    {
        return Cache::remember(
            self::cacheKey($locale),
            self::CACHE_TTL,
            fn (): string => $this->build($locale),
        );
    }

    public function build(string $locale): string
    {
        $profile = Profile::query()->first();

        if ($profile === null) {
            return '';
        }

        return collect([
            $this->profileSection($profile, $locale),
            $this->availabilitySection($locale),
            $this->pagesSection($locale),
            $this->nowSection($locale),
            $this->experienceSection($locale),
            $this->educationSection($locale),
            $this->certificationSection($locale),
            $this->distinctionSection($locale),
            $this->skillSection($locale),
            $this->serviceSection($locale),
            $this->appointmentSection($locale),
            $this->projectSection($locale),
            $this->testimonialSection($locale),
            $this->usesSection($locale),
            $this->blogSection($locale),
        ])->filter()->implode("\n\n");
    }

    /** Liste les pages du site, avec leur URL absolue, pour que l'assistant puisse orienter le visiteur. */
    private function pagesSection(string $locale): string
    {
        $pages = [
            'Accueil' => 'home',
            'À propos' => 'about',
            'Compétences' => 'skills',
            'Liste des projets' => 'projects.index',
            'Témoignages' => 'testimonials.index',
            'Contact' => 'contact.index',
        ];

        $settings = SiteSetting::current();

        if ($settings->blog_enabled) {
            $pages['Blog'] = 'blog.index';
        }

        $optionalPages = [
            'Services (offres et prix « à partir de »)' => ['services', Service::query()->published()->exists()],
            'Estimateur de projet (fourchette de prix et de délai en quelques clics)' => ['estimate.index', $this->estimator->isAvailable()],
            'Prise de rendez-vous (appel ou visio)' => ['appointments.index', $this->calendar->isOpen()],
            'Certifications et formations suivies' => ['certifications', Certification::query()->published()->exists()],
            'Uses (matériel, outils et services que j\'utilise)' => ['uses', UsesItem::query()->published()->exists()],
            'Now (ce sur quoi je travaille en ce moment)' => ['now', $settings->nowText($locale) !== null],
        ];

        foreach ($optionalPages as $label => [$routeName, $isShown]) {
            if ($isShown) {
                $pages[$label] = $routeName;
            }
        }

        $lines = collect($pages)
            ->map(fn (string $routeName, string $label): string => "- {$label} : ".route($routeName, ['locale' => $locale]))
            ->push('- Mon CV en PDF se télécharge depuis la page Contact.');

        return "## Pages du site\n".$lines->implode("\n");
    }

    /** Ma disponibilité telle que l'en-tête du site l'affiche. */
    private function availabilitySection(string $locale): string
    {
        $availability = SiteSetting::current()->publicAvailability();

        $text = match ($availability['status']) {
            AvailabilityStatus::Unavailable->value => 'Je ne suis pas disponible pour de nouvelles missions en ce moment.',
            AvailabilityStatus::From->value => 'Je suis disponible pour de nouvelles missions à partir du '.Carbon::parse($availability['from'])->locale($locale)->isoFormat('LL').'.',
            default => 'Je suis disponible pour de nouvelles missions.',
        };

        return "## Disponibilité\n{$text}";
    }

    /** Le texte de la page « Now ». */
    private function nowSection(string $locale): string
    {
        $text = SiteSetting::current()->nowText($locale);

        return $text === null ? '' : "## En ce moment (page Now)\n".$this->plain($text);
    }

    private function certificationSection(string $locale): string
    {
        $certifications = Certification::query()->published()->orderBy('sort_order')->orderByDesc('issued_on')->get();

        if ($certifications->isEmpty()) {
            return '';
        }

        $lines = $certifications->map(function (Certification $certification) use ($locale): string {
            $line = "- {$certification->getTranslation('name', $locale)}, {$certification->issuer} ({$certification->issued_on->format('m/Y')})";

            return $certification->isExpired() ? $line.', expirée' : $line;
        });

        return "## Certifications et formations suivies\n".$lines->implode("\n");
    }

    private function distinctionSection(string $locale): string
    {
        $distinctions = Distinction::query()->published()->orderBy('sort_order')->orderByDesc('awarded_on')->get();

        if ($distinctions->isEmpty()) {
            return '';
        }

        $lines = $distinctions->map(function (Distinction $distinction) use ($locale): string {
            $line = "- {$distinction->getTranslation('title', $locale)}";

            if (filled($distinction->issuer)) {
                $line .= ", {$distinction->issuer}";
            }

            $line .= " ({$distinction->awarded_on->format('m/Y')})";

            return filled($description = $distinction->getTranslation('description', $locale))
                ? $line.' : '.$this->plain($description)
                : $line;
        });

        return "## Distinctions\n".$lines->implode("\n");
    }

    /** Mes offres publiées, avec leur prix « à partir de » dans chaque devise proposée. */
    private function serviceSection(string $locale): string
    {
        $services = Service::query()->published()->with('estimatorItem')->orderBy('sort_order')->get();

        if ($services->isEmpty()) {
            return '';
        }

        $lines = $services->map(function (Service $service) use ($locale): string {
            $line = "- {$service->getTranslation('title', $locale)}";
            $parts = collect();

            if (filled($tagline = $service->getTranslation('tagline', $locale))) {
                $line .= ' : '.$this->plain($tagline);
            }

            if (filled($description = $service->getTranslation('description', $locale))) {
                $parts->push('Description : '.$this->plain($description));
            }

            if (($includes = $service->includesList($locale)) !== []) {
                $parts->push('Compris : '.implode(' ; ', $includes));
            }

            if (filled($prices = $this->startingPrices($service))) {
                $parts->push("Prix à partir de : {$prices}");
            }

            return $parts->isEmpty() ? $line : $line."\n  ".$parts->implode("\n  ");
        });

        return "## Services proposés\nLes prix sont indicatifs ; le prix final dépend du projet.\n".$lines->implode("\n");
    }

    /** Prix « à partir de » d'une offre dans chaque devise où il existe, ex. « 1500 CHF, 1600 USD ». */
    private function startingPrices(Service $service): string
    {
        $currencies = $service->price_mode === ServicePriceMode::Manual
            ? array_keys(array_filter((array) $service->manual_prices, fn (mixed $amount): bool => (int) $amount > 0))
            : $this->estimator->currencies();

        return collect($currencies)
            ->map(fn (string $currency): ?array => $this->estimator->startingPrice($service, $currency))
            ->filter()
            ->unique(fn (array $price): string => $price['currency'])
            ->map(fn (array $price): string => "{$price['amount']} {$price['currency']}".($price['billing'] === EstimatorBilling::Hourly->value ? ' de l\'heure' : ''))
            ->implode(', ');
    }

    /** Les types de rendez-vous qu'un visiteur peut réserver. */
    private function appointmentSection(string $locale): string
    {
        if (! $this->calendar->isOpen()) {
            return '';
        }

        $lines = AppointmentType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function (AppointmentType $type) use ($locale): string {
                $line = "- {$type->getTranslation('name', $locale)} ({$type->duration_minutes} min)";

                return filled($description = $type->getTranslation('description', $locale))
                    ? $line.' : '.$this->plain($description)
                    : $line;
            });

        return "## Rendez-vous\nTypes de rendez-vous réservables sur la page de prise de rendez-vous :\n".$lines->implode("\n");
    }

    /** Les avis approuvés, ceux à la une d'abord. */
    private function testimonialSection(string $locale): string
    {
        $testimonials = Testimonial::query()
            ->where('status', TestimonialStatus::Approved)
            ->orderByDesc('is_featured')
            ->latest('submitted_at')
            ->limit(self::TESTIMONIAL_LIMIT)
            ->get();

        if ($testimonials->isEmpty()) {
            return '';
        }

        $lines = $testimonials->map(function (Testimonial $testimonial) use ($locale): string {
            $author = filled($testimonial->author_role) ? "{$testimonial->author_name} ({$testimonial->author_role})" : $testimonial->author_name;
            $text = $testimonial->getTranslation('highlight', $locale) ?: $testimonial->getTranslation('content', $locale);

            return "- {$author} : « ".Str::limit($this->plain($text), 400).' »';
        });

        return "## Avis de clients et collègues\n".$lines->implode("\n");
    }

    /** Ce que j'utilise au quotidien (page « Uses »). */
    private function usesSection(string $locale): string
    {
        $items = UsesItem::query()->published()->orderBy('sort_order')->orderBy('name')->get();

        if ($items->isEmpty()) {
            return '';
        }

        $lines = $items->groupBy(fn (UsesItem $item): string => $item->category->value)
            ->map(fn ($group, string $category): string => "- {$category} : ".$group->pluck('name')->implode(', '));

        return "## Mes outils (page Uses)\n".$lines->implode("\n");
    }

    private function profileSection(Profile $profile, string $locale): string
    {
        $lines = ['## Profil', "Nom : {$profile->name}"];

        foreach (['headline' => 'Titre', 'bio_short' => 'Résumé', 'bio_full' => 'Présentation'] as $field => $label) {
            $value = $profile->getTranslation($field, $locale);

            if (filled($value)) {
                $lines[] = "{$label} : ".$this->plain($value);
            }
        }

        foreach (['location' => 'Localisation', 'email' => 'Email', 'phone' => 'Téléphone'] as $field => $label) {
            if (filled($profile->{$field})) {
                $lines[] = "{$label} : {$profile->{$field}}";
            }
        }

        foreach ((array) $profile->social_links as $key => $link) {
            $url = is_array($link) ? ($link['url'] ?? null) : $link;
            $name = is_array($link) ? ($link['label'] ?? $link['name'] ?? $key) : $key;

            if (filled($url)) {
                $lines[] = "Lien ({$name}) : {$url}";
            }
        }

        return implode("\n", $lines);
    }

    private function experienceSection(string $locale): string
    {
        $experiences = Experience::query()
            ->published()
            ->with('highlights')
            ->orderByRaw('end_date is null desc')
            ->orderByDesc('start_date')
            ->get();

        if ($experiences->isEmpty()) {
            return '';
        }

        $lines = $experiences->map(function (Experience $experience) use ($locale): string {
            $period = $experience->start_date->format('m/Y').' - '.($experience->end_date?->format('m/Y') ?? 'aujourd\'hui');
            $line = "- {$experience->getTranslation('role', $locale)} chez ".PostMentions::token('experience', $experience->id, $experience->company)." ({$period})";

            if (filled($experience->location)) {
                $line .= ", {$experience->location}";
            }

            if (filled($description = $experience->getTranslation('description', $locale))) {
                $line .= ' : '.$this->plain($description);
            }

            $highlights = $experience->highlights
                ->sortBy('sort_order')
                ->map(fn ($highlight): string => '  * '.$this->plain($highlight->getTranslation('text', $locale)));

            return $highlights->isEmpty() ? $line : $line."\n".$highlights->implode("\n");
        });

        return "## Expériences professionnelles\n".$lines->implode("\n");
    }

    private function educationSection(string $locale): string
    {
        $educations = Education::query()->published()->orderByDesc('end_date')->get();

        if ($educations->isEmpty()) {
            return '';
        }

        $lines = $educations->map(function (Education $education) use ($locale): string {
            $period = $education->start_date?->format('Y').' - '.($education->end_date?->format('Y') ?? 'aujourd\'hui');

            return trim("- {$education->getTranslation('degree', $locale)} {$education->getTranslation('field', $locale)}, {$education->institution} ({$period})");
        });

        return "## Formation\n".$lines->implode("\n");
    }

    private function skillSection(string $locale): string
    {
        $domains = Domain::query()
            ->published()
            ->with(['skills' => fn ($query) => $query->published()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Domain $domain): bool => $domain->skills->isNotEmpty());

        if ($domains->isEmpty()) {
            return '';
        }

        $lines = $domains->map(fn (Domain $domain): string => "- {$domain->getTranslation('label', $locale)} : ".$domain->skills
            ->map(fn ($skill): string => $skill->getTranslation('name', $locale))
            ->implode(', '));

        return "## Compétences\n".$lines->implode("\n");
    }

    private function projectSection(string $locale): string
    {
        $projects = Project::query()
            ->where('status', ProjectStatus::Published)
            ->with('technologies')
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit(self::PROJECT_LIMIT)
            ->get();

        if ($projects->isEmpty()) {
            return '';
        }

        $lines = $projects->map(function (Project $project) use ($locale): string {
            $parts = collect(['tagline' => 'Accroche', 'client' => 'Client', 'role' => 'Mon rôle', 'context' => 'Contexte', 'challenges' => 'Défis', 'realization' => 'Réalisation', 'result' => 'Résultat'])
                ->map(fn (string $label, string $field): ?string => filled($value = $project->getTranslation($field, $locale))
                    ? "{$label} : ".$this->plain($value)
                    : null)
                ->filter();

            if ($project->started_on !== null) {
                $parts->push('Période : '.$project->started_on->format('m/Y').' - '.($project->ended_on?->format('m/Y') ?? 'aujourd\'hui'));
            }

            if ($project->team_size !== null) {
                $parts->push("Taille de l'équipe : {$project->team_size}");
            }

            if ($project->technologies->isNotEmpty()) {
                $parts->push('Technologies : '.$project->technologies
                    ->map(fn ($technology): string => PostMentions::token('technology', $technology->id, $technology->name))
                    ->implode(', '));
            }

            $parts->push('Page du projet : '.route('projects.show', ['locale' => $locale, 'project' => $project->slug]));

            foreach (['demo_url' => 'Démo', 'repo_url' => 'Code source'] as $field => $label) {
                if (filled($project->{$field})) {
                    $parts->push("{$label} : {$project->{$field}}");
                }
            }

            return '- '.PostMentions::token('project', $project->id, $project->getTranslation('title', $locale))."\n  ".$parts->implode("\n  ");
        });

        return "## Projets\n".$lines->implode("\n");
    }

    /** Articles publiés du blog, avec leur résumé et leur URL, pour que l'assistant puisse les citer. */
    private function blogSection(string $locale): string
    {
        if (! SiteSetting::current()->blog_enabled) {
            return '';
        }

        $posts = Post::query()->published()->latest('published_at')->limit(self::POST_LIMIT)->get();

        if ($posts->isEmpty()) {
            return '';
        }

        $lines = $posts->map(function (Post $post) use ($locale): string {
            $line = '- '.PostMentions::token('post', $post->id, $post->getTranslation('title', $locale));

            if (filled($excerpt = $post->getTranslation('excerpt', $locale))) {
                $line .= ' : '.$this->plain($excerpt);
            }

            return $line."\n  Article : ".route('blog.show', ['locale' => $locale, 'post' => $post->slug]);
        });

        return "## Articles du blog\n".$lines->implode("\n");
    }

    /** Aplatit le texte (balises, retours à la ligne) pour garder un contexte compact. */
    private function plain(?string $text): string
    {
        return trim((string) preg_replace('/\s+/', ' ', strip_tags(PostMentions::plain($text))));
    }
}

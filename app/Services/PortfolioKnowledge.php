<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\Domain;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Rassemble, en texte, tout ce que le site publie sur le propriétaire (profil,
 * expériences, formation, compétences, projets) pour servir de connaissance à
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
            $this->pagesSection($locale),
            $this->experienceSection($locale),
            $this->educationSection($locale),
            $this->skillSection($locale),
            $this->projectSection($locale),
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

        if (SiteSetting::current()->blog_enabled) {
            $pages['Blog'] = 'blog.index';
        }

        $lines = collect($pages)
            ->map(fn (string $routeName, string $label): string => "- {$label} : ".route($routeName, ['locale' => $locale]));

        return "## Pages du site\n".$lines->implode("\n");
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
            $line = "- {$experience->getTranslation('role', $locale)} chez {$experience->company} ({$period})";

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
            $parts = collect(['context' => 'Contexte', 'realization' => 'Réalisation', 'result' => 'Résultat'])
                ->map(fn (string $label, string $field): ?string => filled($value = $project->getTranslation($field, $locale))
                    ? "{$label} : ".$this->plain($value)
                    : null)
                ->filter();

            if ($project->technologies->isNotEmpty()) {
                $parts->push('Technologies : '.$project->technologies->pluck('name')->implode(', '));
            }

            $parts->push('Page du projet : '.route('projects.show', ['locale' => $locale, 'project' => $project->slug]));

            foreach (['demo_url' => 'Démo', 'repo_url' => 'Code source'] as $field => $label) {
                if (filled($project->{$field})) {
                    $parts->push("{$label} : {$project->{$field}}");
                }
            }

            return "- {$project->getTranslation('title', $locale)}\n  ".$parts->implode("\n  ");
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
            $line = "- {$post->getTranslation('title', $locale)}";

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

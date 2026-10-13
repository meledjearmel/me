<?php

namespace App\Services\Ai;

use App\Ai\Agents\CvTailorWriter;
use App\Models\Experience;
use App\Models\ExperienceHighlight;
use App\Models\JobApplication;
use App\Models\Profile;
use App\Models\Skill;

/**
 * Adapte le CV d'une candidature à son offre : l'IA réécrit le titre et le résumé, choisit
 * et ordonne les réalisations parmi celles du site et met des compétences en avant. Tout
 * identifiant inconnu est écarté : le CV adapté ne contient que des faits du site.
 * Partagé par l'administration, l'API et le serveur MCP.
 */
class CvTailor
{
    public function __construct(private readonly TextAssistService $assist) {}

    /**
     * Fait adapter le CV et l'enregistre sur la candidature. False si aucun fournisseur n'a répondu.
     */
    public function tailor(JobApplication $application): bool
    {
        $material = $this->material($application->locale);
        $profile = $application->jobProfile;
        $currentSummary = $profile?->getTranslation('cv_description', $application->locale, false)
            ?: Profile::query()->first()?->getTranslation('bio_short', $application->locale);

        $prompt = implode("\n\n---\n\n", array_filter([
            "Offre d'emploi :\n\"\"\"\n".($application->job_posting_text ?: "Poste : {$application->position} chez {$application->company}")."\n\"\"\"",
            "Résumé actuel du CV :\n{$currentSummary}",
            $material['text'],
        ]));

        $tailoring = $this->assist->promptJson(
            new CvTailorWriter($application->locale),
            $prompt,
            fn (array $data): ?array => $this->normalize($data),
        );

        if ($tailoring === null) {
            return false;
        }

        $this->save($application, $tailoring);

        return true;
    }

    /**
     * Enregistre une adaptation (de l'IA du site, d'un agent MCP ou d'une modification à la main) ;
     * les projets suivent toujours ceux de la candidature.
     *
     * @param  array<string, mixed>  $tailoring
     */
    public function save(JobApplication $application, array $tailoring): void
    {
        $application->forceFill([
            'cv_tailoring' => [
                ...collect($tailoring)->only(['title', 'summary', 'experiences', 'skill_ids', 'reasoning'])->all(),
                'project_ids' => $application->projects()->pluck('projects.id')->all(),
            ],
            'cv_tailored_at' => now(),
        ])->save();
    }

    /**
     * Ramène une adaptation aux éléments existants ; null sans titre ni résumé.
     *
     * @param  array<string, mixed>  $data  {title, summary, experiences: [{experience_id, highlight_ids}], skill_ids, reasoning}
     * @return array{title: string|null, summary: string|null, experiences: array<int, list<int>>, skill_ids: list<int>, reasoning: string|null}|null
     */
    public function normalize(array $data): ?array
    {
        $text = fn (mixed $value, int $max): ?string => is_string($value) && trim($value) !== ''
            ? mb_substr(trim((string) preg_replace('/\s*\((?:id|identifiant)\s*:?\s*\d+\)/iu', '', $value)), 0, $max)
            : null;
        $title = $text($data['title'] ?? null, 120);
        $summary = $text($data['summary'] ?? null, 1500);

        if ($title === null && $summary === null) {
            return null;
        }

        $highlightsByExperience = ExperienceHighlight::query()->get(['id', 'experience_id'])->groupBy('experience_id')
            ->map(fn ($highlights) => $highlights->pluck('id')->all());
        $skillIds = Skill::query()->published()->pluck('id')->all();
        $ints = fn (mixed $values): array => collect(is_array($values) ? $values : [])
            ->map(fn (mixed $id): int|false => filter_var($id, FILTER_VALIDATE_INT))
            ->filter(fn (int|false $id): bool => $id !== false)
            ->unique()
            ->values()
            ->all();

        $experiences = [];

        foreach ((array) ($data['experiences'] ?? []) as $entry) {
            $experienceId = filter_var(is_array($entry) ? ($entry['experience_id'] ?? null) : null, FILTER_VALIDATE_INT);
            $known = $highlightsByExperience->get($experienceId, []);
            $chosen = array_values(array_intersect($ints($entry['highlight_ids'] ?? []), $known));

            if ($experienceId !== false && $chosen !== []) {
                $experiences[$experienceId] = array_slice($chosen, 0, 6);
            }
        }

        return [
            'title' => $title,
            'summary' => $summary,
            'experiences' => $experiences,
            'skill_ids' => array_slice(array_values(array_intersect($ints($data['skill_ids'] ?? []), $skillIds)), 0, 15),
            'reasoning' => $text($data['reasoning'] ?? null, 1000),
        ];
    }

    /**
     * Les expériences (avec leurs réalisations) et les compétences du site, avec leurs
     * identifiants : de quoi choisir, en texte pour l'IA et en données pour un agent MCP.
     *
     * @return array{text: string, experiences: list<array{id: int, company: string, role: string, highlights: list<array{id: int, text: string}>}>, skills: list<array{id: int, name: string}>}
     */
    public function material(string $locale): array
    {
        $experiences = Experience::query()->published()->with('highlights')->orderByDesc('start_date')->get()
            ->map(fn (Experience $experience): array => [
                'id' => $experience->id,
                'company' => $experience->company,
                'role' => $experience->getTranslation('role', $locale),
                'highlights' => $experience->highlights->sortBy('sort_order')
                    ->map(fn (ExperienceHighlight $highlight): array => ['id' => $highlight->id, 'text' => $highlight->getTranslation('text', $locale)])
                    ->values()
                    ->all(),
            ])
            ->all();
        $skills = Skill::query()->published()->orderBy('sort_order')->get()
            ->map(fn (Skill $skill): array => ['id' => $skill->id, 'name' => $skill->getTranslation('name', $locale)])
            ->all();

        $text = "Expériences (identifiant, poste, entreprise) et réalisations (identifiant : texte) :\n"
            .collect($experiences)->map(fn (array $experience): string => "- Expérience {$experience['id']} · {$experience['role']} · {$experience['company']}\n"
                .collect($experience['highlights'])->map(fn (array $highlight): string => "    - {$highlight['id']} : {$highlight['text']}")->implode("\n"))->implode("\n")
            ."\n\nCompétences (identifiant : nom) :\n"
            .collect($skills)->map(fn (array $skill): string => "- {$skill['id']} : {$skill['name']}")->implode("\n");

        return ['text' => $text, 'experiences' => $experiences, 'skills' => $skills];
    }
}

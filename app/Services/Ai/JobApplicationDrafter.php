<?php

namespace App\Services\Ai;

use App\Ai\Agents\JobApplicationWriter;
use App\Enums\ProjectStatus;
use App\Models\JobProfile;
use App\Models\Project;
use App\Services\PortfolioKnowledge;
use App\Services\PostMentions;

/**
 * Prépare une page de candidature d'après une offre d'emploi, avec ce que le site sait du
 * parcours : entreprise, poste, mot d'accompagnement, projets et profil métier choisis
 * parmi ceux publiés. Rien n'est enregistré : le développeur relit avant.
 */
class JobApplicationDrafter
{
    public function __construct(
        private readonly TextAssistService $assist,
        private readonly PortfolioKnowledge $knowledge,
        private readonly PostMentions $mentions,
    ) {}

    /**
     * Le mot d'accompagnement tel qu'une IA l'a écrit, rendu sûr : les mentions vers un élément
     * inconnu ou non publié redeviennent leur nom, et les identifiants égarés dans le texte
     * (« App Station (id 1) ») disparaissent. Les jetons valides restent : ce sont des liens.
     */
    public function cleanIntro(string $intro): string
    {
        $withoutIds = (string) preg_replace('/\s*\((?:id|identifiant)\s*:?\s*\d+\)/iu', '', $intro);

        return $this->mentions->withoutUnknownMentions($withoutIds)['text'];
    }

    /**
     * @return array{company: string, contact_name: string|null, position: string, intro: string, project_ids: list<int>, job_profile_id: int|null, reasoning: string|null}|null
     */
    public function draft(string $jobPosting, string $locale): ?array
    {
        $projects = Project::query()->where('status', ProjectStatus::Published)->orderBy('sort_order')->get();
        $profiles = JobProfile::query()->published()->orderBy('sort_order')->get();
        $projectIds = $projects->modelKeys();
        $profileIds = $profiles->modelKeys();

        // Le libellé du jeton est le titre dans la langue de la page : c'est lui qui s'affiche.
        $catalog = "Projets (identifiant · jeton de mention à recopier dans le texte — accroche) :\n"
            .$projects->map(fn (Project $project): string => "- identifiant {$project->id} · "
                .PostMentions::token('project', $project->id, $project->getTranslation('title', $locale))
                .(filled($project->getTranslation('tagline', $locale)) ? ' — '.$project->getTranslation('tagline', $locale) : ''))->implode("\n")
            ."\n\nProfils métier (identifiant : libellé) :\n"
            .$profiles->map(fn (JobProfile $profile): string => "- {$profile->id} : {$profile->getTranslation('label', 'fr')}")->implode("\n");

        $prompt = "Offre d'emploi :\n\"\"\"\n{$jobPosting}\n\"\"\"\n\n---\n\nPortfolio :\n"
            .$this->knowledge->forLocale('fr')."\n\n---\n\n{$catalog}";

        return $this->assist->promptJson(new JobApplicationWriter($locale), $prompt, function (array $data) use ($projectIds, $profileIds): ?array {
            $text = fn (mixed $value, int $max): ?string => is_string($value) && trim($value) !== '' ? mb_substr(trim($value), 0, $max) : null;
            $company = $text($data['company'] ?? null, 255);
            $position = $text($data['position'] ?? null, 255);
            $intro = $text($this->cleanIntro((string) ($data['intro'] ?? '')), 5000);

            if ($company === null || $position === null || $intro === null) {
                return null;
            }

            $profileId = filter_var($data['job_profile_id'] ?? null, FILTER_VALIDATE_INT);

            return [
                'company' => $company,
                'contact_name' => $text($data['contact_name'] ?? null, 255),
                'position' => $position,
                'intro' => $intro,
                'project_ids' => collect((array) ($data['project_ids'] ?? []))
                    ->map(fn (mixed $id): int|false => filter_var($id, FILTER_VALIDATE_INT))
                    ->filter(fn (int|false $id): bool => $id !== false && in_array($id, $projectIds, true))
                    ->unique()
                    ->take(6)
                    ->values()
                    ->all(),
                'job_profile_id' => in_array($profileId, $profileIds, true) ? $profileId : null,
                'reasoning' => $text($data['reasoning'] ?? null, 1500),
            ];
        });
    }
}

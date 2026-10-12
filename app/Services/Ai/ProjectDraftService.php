<?php

namespace App\Services\Ai;

use App\Models\Domain;
use App\Models\JobProfile;
use App\Models\Technology;
use App\Services\GitHubRepositoryReader;
use Illuminate\Validation\ValidationException;

/**
 * Pré-remplissage d'un projet par l'IA, partagé par l'administration et l'API : assemble
 * la source (dépôt GitHub public, notes), fait rédiger les champs en français, puis
 * ajoute ce que le dépôt dit sans IA (liens, open source). Rien n'est enregistré.
 */
class ProjectDraftService
{
    public function __construct(
        private readonly TextAssistService $assist,
        private readonly GitHubRepositoryReader $repositories,
    ) {}

    /**
     * Null si aucun fournisseur d'IA n'a répondu.
     *
     * @return array<string, mixed>|null
     *
     * @throws ValidationException Dépôt introuvable, ou privé : les fournisseurs gratuits
     *                             peuvent garder ce qu'on leur envoie, le code d'un client n'y va pas.
     */
    public function draft(?string $repositoryName, ?string $notes): ?array
    {
        $repository = null;
        $sources = [];

        if (filled($repositoryName)) {
            $repository = $this->repositories->read($repositoryName);

            if ($repository === null) {
                throw ValidationException::withMessages([
                    'github_repository' => __('Dépôt GitHub introuvable ou inaccessible.'),
                ]);
            }

            if ($repository['private']) {
                throw ValidationException::withMessages([
                    'github_repository' => __('Un dépôt privé n\'est pas envoyé à l\'IA : collez plutôt des notes, ou passez par le serveur MCP.'),
                ]);
            }

            $sources[] = $repository['source'];
        }

        if (filled($notes)) {
            $sources[] = "Notes du développeur :\n{$notes}";
        }

        $draft = $this->assist->draftProject(implode("\n\n---\n\n", $sources), $this->catalog());

        if ($draft === null || $repository === null) {
            return $draft;
        }

        return [
            ...$draft,
            'github_repository' => $repository['full_name'],
            'repo_url' => $repository['url'],
            'demo_url' => $repository['homepage'],
            'is_open_source' => true,
        ];
    }

    /**
     * Technologies, domaines et profils métier du site, libellés indexés par identifiant
     * (aussi exposés par le serveur MCP).
     *
     * @return array{technologies: array<int, string>, domains: array<int, string>, job_profiles: array<int, string>}
     */
    public function catalog(): array
    {
        return [
            'technologies' => Technology::query()->orderBy('name')->pluck('name', 'id')->all(),
            'domains' => Domain::query()->get()->mapWithKeys(fn (Domain $domain): array => [$domain->id => $domain->getTranslation('label', 'fr')])->all(),
            'job_profiles' => JobProfile::query()->get()->mapWithKeys(fn (JobProfile $profile): array => [$profile->id => $profile->getTranslation('label', 'fr')])->all(),
        ];
    }
}

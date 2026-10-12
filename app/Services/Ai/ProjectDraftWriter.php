<?php

namespace App\Services\Ai;

use App\Concerns\SavesProjects;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Services\GitHubActivity;
use App\Services\PostMentions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Écriture des brouillons de projet par le serveur MCP, en français : création,
 * remplacement complet ou modification de quelques champs. L'anglais déjà saisi est gardé.
 */
class ProjectDraftWriter
{
    use SavesProjects;

    /** Champs traduisibles écrits en français. */
    public const array TRANSLATABLE = ['title', 'tagline', 'role', 'client', 'platform', 'context', 'challenges', 'realization', 'result'];

    /** Champs où le site affiche les mentions « @[Libellé](type:id) ». */
    public const array WITH_MENTIONS = ['context', 'challenges', 'realization', 'result'];

    public function __construct(
        private readonly PostMentions $mentions,
        private readonly GitHubActivity $github,
    ) {}

    /**
     * Le brouillon (ou un projet) dans la forme d'entrée de save-project-draft, en français.
     *
     * @return array<string, mixed>
     */
    public function toInput(Project $project): array
    {
        $project->loadMissing(['technologies:id', 'domains:id', 'jobProfiles:id']);
        $french = fn (string $field): ?string => $project->getTranslation($field, 'fr', false) ?: null;

        return [
            ...collect(self::TRANSLATABLE)->mapWithKeys(fn (string $field): array => [$field => $french($field)])->all(),
            'slug' => $project->slug,
            'decisions' => collect($project->decisions ?? [])->map(fn (array $decision): array => [
                'choice' => $decision['choice']['fr'] ?? null,
                'reason' => $decision['reason']['fr'] ?? null,
            ])->all(),
            'key_figures' => collect($project->key_figures ?? [])->map(fn (array $figure): array => [
                'value' => $figure['value'] ?? null,
                'label' => $figure['label']['fr'] ?? null,
            ])->all(),
            'started_on' => $project->started_on?->format('Y-m'),
            'ended_on' => $project->ended_on?->format('Y-m'),
            'team_size' => $project->team_size,
            'technologies' => $project->technologies->modelKeys(),
            'domains' => $project->domains->modelKeys(),
            'job_profiles' => $project->jobProfiles->modelKeys(),
            'related_projects' => $this->relatedProjectIds($project),
            'github_repository' => $project->github_repository,
            'repo_url' => $project->repo_url,
            'demo_url' => $project->demo_url,
            'is_open_source' => $project->is_open_source,
        ];
    }

    /**
     * L'anglais du projet, dans la forme de save-project-translation.
     *
     * @return array<string, mixed>
     */
    public function english(Project $project): array
    {
        return [
            ...collect(self::TRANSLATABLE)->mapWithKeys(fn (string $field): array => [$field => $project->getTranslation($field, 'en', false) ?: null])->all(),
            'decisions' => collect($project->decisions ?? [])->map(fn (array $decision): array => [
                'choice' => $decision['choice']['en'] ?? null,
                'reason' => $decision['reason']['en'] ?? null,
            ])->all(),
            'key_figure_labels' => collect($project->key_figures ?? [])->map(fn (array $figure): ?string => $figure['label']['en'] ?? null)->all(),
        ];
    }

    /**
     * Écrit l'anglais fourni (champs absents inchangés) ; les choix techniques et libellés
     * de chiffres clés suivent l'ordre du français.
     *
     * @param  array<string, mixed>  $english
     */
    public function setEnglish(Project $project, array $english): void
    {
        foreach (self::TRANSLATABLE as $field) {
            if (! array_key_exists($field, $english)) {
                continue;
            }

            filled($english[$field])
                ? $project->setTranslation($field, 'en', in_array($field, self::WITH_MENTIONS, true)
                    ? $this->mentions->withoutUnknownMentions($english[$field])['text']
                    : PostMentions::plain($english[$field]))
                : $project->forgetTranslation($field, 'en');
        }

        if (array_key_exists('decisions', $english)) {
            $project->decisions = collect($project->decisions ?? [])->values()->map(fn (array $decision, int $index): array => [
                'choice' => [...$decision['choice'], 'en' => $english['decisions'][$index]['choice'] ?? ''],
                'reason' => [...$decision['reason'], 'en' => $english['decisions'][$index]['reason'] ?? ''],
            ])->all();
        }

        if (array_key_exists('key_figure_labels', $english)) {
            $project->key_figures = collect($project->key_figures ?? [])->values()->map(fn (array $figure, int $index): array => [
                ...$figure,
                'label' => [...$figure['label'], 'en' => $english['key_figure_labels'][$index] ?? ''],
            ])->all();
        }

        $project->save();
    }

    /**
     * Valide puis enregistre le brouillon ; renvoie ce que l'agent doit savoir : mentions
     * retirées (élément inconnu ou non publié, remplacé par son libellé) et dépôt lié.
     *
     * @param  array<string, mixed>  $input
     * @return list<string>
     *
     * @throws ValidationException
     */
    public function save(Project $project, array $input): array
    {
        $this->ensureGitHubRepository($input['github_repository'] ?? null);
        $input['github_repository'] = self::repositoryName($input['github_repository'] ?? null);
        $data = Validator::make($input, $this->rules($project))->validate();
        $notes = $this->linkRepository($data);
        $removedMentions = [];

        foreach (self::TRANSLATABLE as $field) {
            if (! filled($data[$field] ?? null)) {
                continue;
            }

            if (in_array($field, self::WITH_MENTIONS, true)) {
                ['text' => $data[$field], 'removed' => $removed] = $this->mentions->withoutUnknownMentions($data[$field]);
                $removedMentions = [...$removedMentions, ...$removed];
            } else {
                // Hors des textes de l'étude de cas, le jeton ne serait pas affiché.
                $data[$field] = PostMentions::plain($data[$field]);
            }
        }

        DB::transaction(function () use ($project, $data): void {
            foreach (self::TRANSLATABLE as $field) {
                filled($data[$field] ?? null)
                    ? $project->setTranslation($field, 'fr', $data[$field])
                    : $project->forgetTranslation($field, 'fr');
            }

            $english = fn (array $items, int $index, string $key): string => $items[$index][$key]['en'] ?? '';
            $decisions = array_values($project->decisions ?? []);
            $figures = array_values($project->key_figures ?? []);

            $project->fill([
                'status' => $project->status ?? ProjectStatus::Draft,
                'slug' => $data['slug'],
                // L'anglais d'un choix ou d'un chiffre déjà traduit suit sa position.
                'decisions' => collect($data['decisions'] ?? [])->values()->map(fn (array $decision, int $index): array => [
                    'choice' => ['fr' => $decision['choice'], 'en' => $english($decisions, $index, 'choice')],
                    'reason' => ['fr' => $decision['reason'], 'en' => $english($decisions, $index, 'reason')],
                ])->all(),
                'key_figures' => collect($data['key_figures'] ?? [])->values()->map(fn (array $figure, int $index): array => [
                    'value' => $figure['value'],
                    'label' => ['fr' => $figure['label'], 'en' => $english($figures, $index, 'label')],
                ])->all(),
                'started_on' => isset($data['started_on']) ? "{$data['started_on']}-01" : null,
                'ended_on' => isset($data['ended_on']) ? "{$data['ended_on']}-01" : null,
                'team_size' => $data['team_size'] ?? null,
                'github_repository' => $data['github_repository'] ?? null,
                'repo_url' => $data['repo_url'] ?? null,
                'demo_url' => $data['demo_url'] ?? null,
                'is_open_source' => $data['is_open_source'] ?? false,
            ])->save();

            $project->technologies()->sync($data['technologies'] ?? []);
            $project->domains()->sync($data['domains'] ?? []);
            $project->jobProfiles()->sync($data['job_profiles'] ?? []);
            $this->syncRelatedProjects($project, $data['related_projects'] ?? []);
        });

        if ($removedMentions !== []) {
            $notes[] = 'Mentions retirées (élément inconnu ou non publié, remplacé par son libellé) : '.implode(', ', array_unique($removedMentions));
        }

        return $notes;
    }

    /**
     * « propriétaire/nom » à partir de ce que donne `git remote get-url origin`
     * (https://github.com/…, git@github.com:…, avec ou sans .git) ou du nom lui-même.
     */
    public static function repositoryName(mixed $value): mixed
    {
        if (! is_string($value) || trim($value) === '') {
            return $value === '' ? null : $value;
        }

        $value = trim($value);

        if (preg_match('~github\.com[/:]([A-Za-z0-9-]+)/([A-Za-z0-9._-]+?)(?:\.git)?/?$~i', $value, $matches) === 1) {
            return "{$matches[1]}/{$matches[2]}";
        }

        return $value;
    }

    /**
     * Le portfolio ne connaît que GitHub : une adresse GitLab, Bitbucket… (dépôt d'origine
     * d'un miroir, par exemple) est refusée, avec les dépôts GitHub synchronisés du même nom
     * pour que l'agent fasse confirmer l'équivalent au développeur.
     *
     * @throws ValidationException
     */
    private function ensureGitHubRepository(mixed $value): void
    {
        if (! is_string($value) || ! preg_match('~^(?:[a-z+]+://|[\w.-]+@)([^/:]+)[/:](.+?)(?:\.git)?/?$~i', trim($value), $matches)) {
            return;
        }

        $host = strtolower(preg_replace('/^.*@/', '', $matches[1]));

        if ($host === 'github.com' || $host === 'www.github.com') {
            return;
        }

        $name = $this->comparable(basename($matches[2]));
        $candidates = collect($this->github->cached()['available'] ?? [])
            ->filter(fn (array $repository): bool => $this->comparable(basename($repository['full_name'])) === $name)
            ->map(fn (array $repository): string => $repository['full_name'].($repository['private'] ? ' (privé)' : ''))
            ->values();

        $message = "« {$value} » n'est pas sur GitHub ({$host}), or le portfolio ne relie que des dépôts GitHub. Vérifie les autres remotes (git remote -v) ; ";
        $message .= $candidates->isEmpty()
            ? 'aucun dépôt GitHub synchronisé ne porte ce nom : demande au développeur le miroir GitHub, ou laisse github_repository vide.'
            : 'dépôt(s) GitHub synchronisé(s) du même nom : '.$candidates->implode(', ').'. Fais confirmer l\'équivalent au développeur avant de le passer.';

        throw ValidationException::withMessages(['github_repository' => $message]);
    }

    /** Nom de dépôt comparable d'un hébergeur à l'autre (casse, tirets, points). */
    private function comparable(string $name): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', strtolower($name));
    }

    /**
     * Rattache le dépôt à celui de la dernière synchronisation GitHub (casse exacte) : un
     * dépôt public donne son adresse si elle manque, un dépôt privé n'en expose jamais.
     *
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function linkRepository(array &$data): array
    {
        if (blank($data['github_repository'] ?? null)) {
            return [];
        }

        $repository = $this->github->repository($data['github_repository']);

        if ($repository === null) {
            return ["Dépôt {$data['github_repository']} absent de la dernière synchronisation GitHub : lien gardé, mais l'encart d'activité n'apparaîtra qu'une fois le dépôt synchronisé (Dépôts GitHub → Synchroniser)."];
        }

        $data['github_repository'] = $repository['full_name'];

        if ($repository['private']) {
            if (filled($data['repo_url'] ?? null) && str_contains(strtolower($data['repo_url']), strtolower($repository['full_name']))) {
                $data['repo_url'] = null;
            }

            return ["Dépôt privé {$repository['full_name']} lié : son activité s'affiche sans nom ni lien, et son adresse n'est pas publiée."];
        }

        $data['repo_url'] = filled($data['repo_url'] ?? null) ? $data['repo_url'] : $repository['url'];

        return ["Dépôt public {$repository['full_name']} lié ({$data['repo_url']})."];
    }

    /** @return array<string, mixed> */
    public function rules(Project $project): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('projects', 'slug')->ignore($project->id)],
            'tagline' => ['nullable', 'string', 'max:160'],
            'role' => ['nullable', 'string', 'max:120'],
            'client' => ['nullable', 'string', 'max:120'],
            'platform' => ['nullable', 'string', 'max:120'],
            'context' => ['required', 'string'],
            'challenges' => ['nullable', 'string'],
            'realization' => ['required', 'string'],
            'result' => ['required', 'string'],
            'decisions' => ['nullable', 'array', 'max:6'],
            'decisions.*.choice' => ['required', 'string', 'max:160'],
            'decisions.*.reason' => ['required', 'string', 'max:600'],
            'key_figures' => ['nullable', 'array', 'max:4'],
            'key_figures.*.value' => ['required', 'string', 'max:20'],
            'key_figures.*.label' => ['required', 'string', 'max:80'],
            'started_on' => ['nullable', 'date_format:Y-m'],
            'ended_on' => ['nullable', 'date_format:Y-m', 'after_or_equal:started_on'],
            'team_size' => ['nullable', 'integer', 'min:1', 'max:200'],
            'technologies' => ['nullable', 'array'],
            'technologies.*' => ['integer', 'exists:technologies,id'],
            'domains' => ['nullable', 'array'],
            'domains.*' => ['integer', 'exists:domains,id'],
            'job_profiles' => ['nullable', 'array'],
            'job_profiles.*' => ['integer', 'exists:job_profiles,id'],
            'related_projects' => ['nullable', 'array'],
            'related_projects.*' => ['integer', 'exists:projects,id', Rule::notIn(array_filter([$project->id]))],
            'github_repository' => ['nullable', 'string', 'max:200', 'regex:~^[A-Za-z0-9-]+/[A-Za-z0-9._-]+$~'],
            'repo_url' => ['nullable', 'url', 'max:255'],
            'demo_url' => ['nullable', 'url', 'max:255'],
            'is_open_source' => ['nullable', 'boolean'],
        ];
    }
}

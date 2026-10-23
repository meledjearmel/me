<?php

namespace App\Concerns;

use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Ce dont parle un avis (ou un lien d'invitation) : aucun, un ou plusieurs projets, expériences
 * et formations, rangés dans la table de liaison `{table au singulier}_subjects`.
 */
trait HasSubjects
{
    /** Relations à charger pour afficher les rattachements. */
    public const array SUBJECT_RELATIONS = ['projects', 'experiences', 'educations'];

    /** @return MorphToMany<Project, $this> */
    public function projects(): MorphToMany
    {
        return $this->morphedByMany(Project::class, 'subject', $this->subjectsTable());
    }

    /** @return MorphToMany<Experience, $this> */
    public function experiences(): MorphToMany
    {
        return $this->morphedByMany(Experience::class, 'subject', $this->subjectsTable());
    }

    /** @return MorphToMany<Education, $this> */
    public function educations(): MorphToMany
    {
        return $this->morphedByMany(Education::class, 'subject', $this->subjectsTable());
    }

    /**
     * Remplace les rattachements des types présents dans `$data` ; un type absent reste tel quel.
     *
     * @param  array{project_ids?: list<int>|null, experience_ids?: list<int>|null, education_ids?: list<int>|null}  $data
     */
    public function syncSubjects(array $data): void
    {
        foreach (['project_ids' => 'projects', 'experience_ids' => 'experiences', 'education_ids' => 'educations'] as $key => $relation) {
            if (array_key_exists($key, $data)) {
                $this->{$relation}()->sync($data[$key] ?? []);
            }
        }
    }

    /**
     * Les identifiants rattachés, au format attendu par `syncSubjects()`.
     *
     * @return array{project_ids: list<int>, experience_ids: list<int>, education_ids: list<int>}
     */
    public function subjectIds(): array
    {
        return [
            'project_ids' => $this->projects()->pluck('projects.id')->all(),
            'experience_ids' => $this->experiences()->pluck('experiences.id')->all(),
            'education_ids' => $this->educations()->pluck('educations.id')->all(),
        ];
    }

    /**
     * Les rattachements lisibles dans une langue, dans l'ordre projets, expériences, formations.
     * Les relations doivent être chargées.
     *
     * @return list<array{type: 'project'|'experience'|'education', id: int, label: string, slug: string|null}>
     */
    public function subjectsFor(string $locale): array
    {
        return [
            ...$this->projects->map(fn (Project $project): array => [
                'type' => 'project',
                'id' => $project->id,
                'label' => $project->getTranslation('title', $locale),
                'slug' => $project->slug,
            ]),
            ...$this->experiences->map(fn (Experience $experience): array => [
                'type' => 'experience',
                'id' => $experience->id,
                'label' => $experience->getTranslation('role', $locale).' — '.$experience->company,
                'slug' => null,
            ]),
            ...$this->educations->map(fn (Education $education): array => [
                'type' => 'education',
                'id' => $education->id,
                'label' => $education->getTranslation('degree', $locale).' — '.$education->institution,
                'slug' => null,
            ]),
        ];
    }

    /** Ce dont parle l'avis, en une ligne, ou null pour un avis général. */
    public function subjectLabel(string $locale): ?string
    {
        $labels = array_column($this->subjectsFor($locale), 'label');

        return $labels === [] ? null : implode(' · ', $labels);
    }

    private function subjectsTable(): string
    {
        return str($this->getTable())->singular()->append('_subjects')->toString();
    }
}

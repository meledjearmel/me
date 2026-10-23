<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rattachements multiples d'un avis ou d'un lien d'invitation (`project_ids`, `experience_ids`,
 * `education_ids`). Les anciens champs uniques (`project_id`…) restent acceptés pour l'API :
 * ils remplacent la liste du même type.
 */
trait SubjectValidationRules
{
    /** @var array<string, string> */
    private const array SUBJECT_FIELDS = [
        'project_ids' => 'projects',
        'experience_ids' => 'experiences',
        'education_ids' => 'educations',
    ];

    /** @return array<string, array<int, ValidationRule|string>> */
    protected function subjectRules(): array
    {
        $rules = [];

        foreach (self::SUBJECT_FIELDS as $field => $table) {
            $rules[$field] = ['sometimes', 'nullable', 'array'];
            $rules["{$field}.*"] = ['integer', 'distinct', "exists:{$table},id"];
        }

        return $rules;
    }

    protected function mergeLegacySubjects(): void
    {
        foreach (array_keys(self::SUBJECT_FIELDS) as $field) {
            $legacy = substr($field, 0, -1);

            // Les formulaires de l'admin envoient une valeur vide pour qu'aucune case cochée vide la liste.
            if (is_array($this->input($field))) {
                $this->merge([$field => array_values(array_filter($this->input($field), fn (mixed $id): bool => filled($id)))]);
            }

            if ($this->has($legacy) && ! $this->has($field)) {
                $this->merge([$field => $this->filled($legacy) ? [$this->input($legacy)] : []]);
            }
        }
    }

    /**
     * Les rattachements validés, au format attendu par `syncSubjects()`.
     *
     * @return array{project_ids?: list<int>|null, experience_ids?: list<int>|null, education_ids?: list<int>|null}
     */
    public function subjects(): array
    {
        return $this->safe()->only(array_keys(self::SUBJECT_FIELDS));
    }
}

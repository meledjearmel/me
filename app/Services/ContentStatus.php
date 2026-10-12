<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Enums\PublicationStatus;
use App\Models\Certification;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Post;
use App\Models\Project;
use App\Models\Skill;
use App\Models\UsesItem;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Change le seul statut d'un contenu (fiche de l'administration, API), avec les règles des
 * formulaires : on ne publie pas un contenu dont l'anglais obligatoire manque, et un article
 * publié sans date paraît tout de suite.
 */
class ContentStatus
{
    /**
     * Modèle, statuts possibles et champs traduits à remplir en anglais pour publier.
     *
     * @var array<string, array{model: class-string<Model>, enum: class-string<BackedEnum>, english: list<string>}>
     */
    private const array TYPES = [
        'project' => ['model' => Project::class, 'enum' => ProjectStatus::class, 'english' => ['title', 'context', 'realization', 'result']],
        'post' => ['model' => Post::class, 'enum' => PublicationStatus::class, 'english' => []],
        'experience' => ['model' => Experience::class, 'enum' => PublicationStatus::class, 'english' => ['role']],
        'education' => ['model' => Education::class, 'enum' => PublicationStatus::class, 'english' => ['degree', 'field']],
        'certification' => ['model' => Certification::class, 'enum' => PublicationStatus::class, 'english' => ['name']],
        'uses_item' => ['model' => UsesItem::class, 'enum' => PublicationStatus::class, 'english' => []],
        'skill' => ['model' => Skill::class, 'enum' => PublicationStatus::class, 'english' => ['name']],
    ];

    /** @return list<string> */
    public static function types(): array
    {
        return array_keys(self::TYPES);
    }

    public function find(string $type, int $id): ?Model
    {
        return isset(self::TYPES[$type]) ? self::TYPES[$type]['model']::query()->find($id) : null;
    }

    /**
     * @throws ValidationException
     */
    public function change(string $type, Model $content, string $status): void
    {
        $definition = self::TYPES[$type];
        $newStatus = $definition['enum']::tryFrom($status);

        if ($newStatus === null) {
            throw ValidationException::withMessages(['status' => __('Statut inconnu.')]);
        }

        $publishing = $newStatus->value === 'published';
        $missing = $publishing ? $this->missingEnglish($definition['english'], $content) : [];

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'status' => __('Complétez l\'anglais avant de publier : :fields.', ['fields' => implode(', ', $missing)]),
            ]);
        }

        $content->status = $newStatus;

        if ($content instanceof Post && $publishing && $content->published_at === null) {
            $content->published_at = now();
        }

        $content->save();
    }

    /**
     * Les champs anglais obligatoires encore vides, réalisations d'une expérience et choix
     * techniques ou chiffres clés d'un projet compris.
     *
     * @param  list<string>  $fields
     * @return list<string>
     */
    private function missingEnglish(array $fields, Model $content): array
    {
        $missing = array_values(array_filter($fields, fn (string $field): bool => blank($content->getTranslation($field, 'en', false))));

        if ($content instanceof Project) {
            $untranslatedDecision = collect($content->decisions ?? [])->contains(fn (array $decision): bool => blank($decision['choice']['en'] ?? null) || blank($decision['reason']['en'] ?? null));
            $untranslatedFigure = collect($content->key_figures ?? [])->contains(fn (array $figure): bool => blank($figure['label']['en'] ?? null));

            if ($untranslatedDecision) {
                $missing[] = 'decisions';
            }

            if ($untranslatedFigure) {
                $missing[] = 'key_figures';
            }
        }

        if ($content instanceof Experience && $content->highlights->contains(fn ($highlight): bool => blank($highlight->getTranslation('text', 'en', false)))) {
            $missing[] = 'highlights';
        }

        return $missing;
    }
}

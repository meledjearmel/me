<?php

namespace App\Services\Ai;

use App\Enums\PublicationStatus;
use App\Models\Experience;
use App\Models\Skill;
use App\Services\PostMentions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Écriture des brouillons des contenus de ContentTypes par le serveur MCP : création ou
 * modification des seuls champs envoyés, en français, puis traduction anglaise une fois le
 * français validé. Un contenu publié n'est jamais modifié ici.
 */
class ContentDraftWriter
{
    public function __construct(
        private readonly ContentTypes $types,
        private readonly PostMentions $mentions,
    ) {}

    /**
     * Le contenu en français, dans la forme d'entrée de save-content-draft.
     *
     * @return array<string, mixed>
     */
    public function toInput(string $type, Model $content): array
    {
        $definition = $this->types->get($type);

        return [
            ...collect($definition['fields'])->keys()->mapWithKeys(fn (string $field): array => [$field => $this->plainValue($content, $field)])->all(),
            ...collect($definition['translatable'])->keys()->mapWithKeys(fn (string $field): array => [$field => $content->getTranslation($field, 'fr', false) ?: null])->all(),
            ...collect($definition['relations'])->keys()->mapWithKeys(fn (string $relation): array => [$relation => $this->relationValue($content, $relation, 'fr')])->all(),
        ];
    }

    /**
     * Les champs anglais déjà saisis, pour relire une traduction.
     *
     * @return array<string, mixed>
     */
    public function english(string $type, Model $content): array
    {
        $definition = $this->types->get($type);

        return [
            ...collect($definition['translatable'])->keys()->mapWithKeys(fn (string $field): array => [$field => $content->getTranslation($field, 'en', false) ?: null])->all(),
            ...collect($definition['relations'])->keys()->intersect(['highlights'])->mapWithKeys(fn (string $relation): array => [$relation => $this->relationValue($content, $relation, 'en')])->all(),
        ];
    }

    /**
     * Valide puis enregistre le brouillon en français ; renvoie les mentions retirées.
     *
     * @param  array<string, mixed>  $input  Toutes les valeurs (actuelles complétées des changements).
     * @return list<string>
     *
     * @throws ValidationException
     */
    public function save(string $type, Model $content, array $input): array
    {
        $definition = $this->types->get($type);
        $data = Validator::make($input, $this->rules($type))->validate();
        $removed = [];

        DB::transaction(function () use ($definition, $content, $data, &$removed): void {
            foreach (array_keys($definition['translatable']) as $field) {
                $value = $data[$field] ?? null;

                if (! filled($value)) {
                    $content->forgetTranslation($field, 'fr');

                    continue;
                }

                if (in_array($field, $definition['mentions'], true)) {
                    ['text' => $value, 'removed' => $fieldRemoved] = $this->mentions->withoutUnknownMentions($value);
                    $removed = [...$removed, ...$fieldRemoved];
                } else {
                    $value = PostMentions::plain($value);
                }

                $content->setTranslation($field, 'fr', $value);
            }

            $content->fill(collect($data)->only(array_keys($definition['fields']))->all());
            $content->status ??= PublicationStatus::Draft;
            $content->save();

            if ($content instanceof Experience && array_key_exists('highlights', $definition['relations'])) {
                $this->syncHighlights($content, $data['highlights'] ?? []);
            }

            if ($content instanceof Skill) {
                $content->technologies()->sync($data['technologies'] ?? []);
            }
        });

        return array_values(array_unique($removed));
    }

    /** @return array<string, mixed> */
    public function rules(string $type): array
    {
        $definition = $this->types->get($type);

        return [
            ...collect($definition['fields'])->map(fn (array $field): array => $field['rules'])->all(),
            ...collect($definition['translatable'])->map(fn (array $field): array => $field['rules'])->all(),
            ...collect($definition['relations'])->flatMap(fn (array $relation): array => $relation['rules'])->all(),
        ];
    }

    /**
     * L'anglais d'un brouillon, sans toucher au français : seuls les champs remplis en
     * français sont traduits, les points forts dans le même ordre.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function saveTranslation(string $type, Model $content, array $input): void
    {
        $definition = $this->types->get($type);
        $rules = collect($definition['translatable'])
            ->map(fn (array $field, string $name): array => filled($content->getTranslation($name, 'fr', false))
                ? ['required', ...array_diff($field['rules'], ['nullable', 'required'])]
                : ['nullable', 'string'])
            ->all();

        if ($content instanceof Experience) {
            $count = $content->highlights()->count();
            $rules['highlights'] = ['array', 'size:'.$count];
            $rules['highlights.*'] = ['required', 'string', 'max:300'];
        }

        $data = Validator::make($input, $rules, ['highlights.size' => 'Traduisez les :size réalisations, dans l\'ordre.'])->validate();

        // Un champ vide en français reste vide en anglais.
        $this->setEnglish($type, $content, collect($data)
            ->filter(fn (mixed $value, string $field): bool => $field === 'highlights' || filled($content->getTranslation($field, 'fr', false)))
            ->all());
    }

    /**
     * Écrit l'anglais fourni (champs absents inchangés) ; les réalisations d'une expérience
     * suivent l'ordre du français.
     *
     * @param  array<string, mixed>  $english
     */
    public function setEnglish(string $type, Model $content, array $english): void
    {
        $definition = $this->types->get($type);

        DB::transaction(function () use ($definition, $content, $english): void {
            foreach (array_keys($definition['translatable']) as $field) {
                if (! array_key_exists($field, $english)) {
                    continue;
                }

                filled($english[$field])
                    ? $content->setTranslation($field, 'en', in_array($field, $definition['mentions'], true)
                        ? $this->mentions->withoutUnknownMentions($english[$field])['text']
                        : PostMentions::plain($english[$field]))
                    : $content->forgetTranslation($field, 'en');
            }

            $content->save();

            if ($content instanceof Experience && array_key_exists('highlights', $english)) {
                $content->highlights()->orderBy('sort_order')->get()->values()->each(
                    fn ($highlight, int $index) => $highlight->setTranslation('text', 'en', $english['highlights'][$index] ?? '')->save()
                );
            }
        });
    }

    /**
     * Recrée les points forts dans l'ordre, en gardant l'anglais déjà traduit à la même place.
     *
     * @param  array<int, string>  $texts
     */
    private function syncHighlights(Experience $experience, array $texts): void
    {
        $english = $experience->highlights()->orderBy('sort_order')->get()
            ->map(fn ($highlight): string => $highlight->getTranslation('text', 'en', false))
            ->values();

        $experience->highlights()->delete();

        foreach (array_values($texts) as $index => $text) {
            $experience->highlights()->create([
                'text' => array_filter(['fr' => PostMentions::plain($text), 'en' => $english[$index] ?? '']),
                'sort_order' => $index,
            ]);
        }
    }

    private function plainValue(Model $content, string $field): mixed
    {
        $value = $content->getAttribute($field);

        return match (true) {
            $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
            $value instanceof \BackedEnum => $value->value,
            default => $value,
        };
    }

    /** @return list<mixed> */
    private function relationValue(Model $content, string $relation, string $locale): array
    {
        return match (true) {
            $content instanceof Experience && $relation === 'highlights' => $content->highlights()->orderBy('sort_order')->get()
                ->map(fn ($highlight): string => $highlight->getTranslation('text', $locale, false))->values()->all(),
            $content instanceof Skill && $relation === 'technologies' => $content->technologies()->pluck('technologies.id')->all(),
            default => [],
        };
    }
}

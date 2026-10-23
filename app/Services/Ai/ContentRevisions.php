<?php

namespace App\Services\Ai;

use App\Enums\ProjectStatus;
use App\Enums\PublicationStatus;
use App\Enums\RevisionStatus;
use App\Models\Celebration;
use App\Models\ContentRevision;
use App\Models\Post;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\HasMedia;

/**
 * Révisions d'un contenu publié (ou de la page « Now ») proposées par l'IA : rien ne change
 * sur le site avant qu'elles soient appliquées depuis l'administration ou l'API.
 *
 * Une révision ne garde que les champs modifiés, en français (fr) et en anglais (en), et leur
 * valeur au moment de la proposition (before) pour montrer l'avant/après. Une révision d'image
 * (changes.image) joint l'image proposée à la révision, copiée vers le contenu à l'application.
 */
class ContentRevisions
{
    public const string NOW_PAGE = 'now_page';

    /** Surprise (Celebration) : même relecture avant/après qu'un contenu publié. */
    public const string CELEBRATION = 'celebration';

    /** Champs traduits d'une surprise (fr et en). */
    private const array CELEBRATION_TRANSLATABLE = ['message', 'button_label'];

    /** Champs simples d'une surprise (dans la partie fr). */
    private const array CELEBRATION_FIELDS = ['congratulated_for', 'starts_at', 'ends_at', 'weight', 'chance_percent', 'delay_seconds', 'display_seconds', 'snooze_days'];

    /**
     * Pour chaque liste traduite, son nom côté anglais (les autres champs gardent leur nom).
     *
     * @var array<string, string>
     */
    private const array ENGLISH_LISTS = ['key_figures' => 'key_figure_labels', 'decisions' => 'decisions', 'highlights' => 'highlights'];

    public function __construct(
        private readonly ProjectDraftWriter $projects,
        private readonly PostDraftWriter $posts,
        private readonly ContentDraftWriter $contents,
        private readonly ContentTypes $types,
    ) {}

    /** @return list<string> */
    public function typeNames(): array
    {
        return ['project', 'post', ...array_keys($this->types->all()), self::CELEBRATION, self::NOW_PAGE];
    }

    public function find(string $type, ?int $id): ?Model
    {
        return match (true) {
            $type === self::NOW_PAGE => SiteSetting::current(),
            $type === 'project' => Project::query()->find($id),
            $type === 'post' => Post::query()->find($id),
            $type === self::CELEBRATION => Celebration::query()->find($id),
            array_key_exists($type, $this->types->all()) => $this->types->get($type)['model']::query()->find($id),
            default => null,
        };
    }

    /** Titre lisible du contenu, pour les listes de révisions. */
    public function title(string $type, ?Model $content): string
    {
        if ($content === null) {
            return '(contenu supprimé)';
        }

        return match (true) {
            $type === self::NOW_PAGE => 'Page « Now »',
            $type === 'project', $type === 'post' => $content->getTranslation('title', 'fr'),
            $type === self::CELEBRATION => (string) $content->getAttribute('congratulated_for'),
            default => (function () use ($type, $content): string {
                $title = $this->types->get($type)['title'];

                return in_array($title, $content->translatable ?? [], true) ? $content->getTranslation($title, 'fr') : (string) $content->getAttribute($title);
            })(),
        };
    }

    public function editUrl(string $type, ?Model $content): ?string
    {
        return match (true) {
            $content === null => null,
            $type === self::NOW_PAGE => route('admin.now-page.edit'),
            $type === 'project' => route('admin.projects.edit', $content),
            $type === 'post' => route('admin.posts.edit', $content),
            $type === self::CELEBRATION => route('admin.celebrations.edit', $content),
            default => route($this->types->get($type)['route'], $content),
        };
    }

    /**
     * Valeurs actuelles du contenu : français dans la forme des outils de brouillon, anglais
     * dans celle des outils de traduction.
     *
     * @return array{fr: array<string, mixed>, en: array<string, mixed>}
     */
    public function snapshot(string $type, Model $content): array
    {
        return match (true) {
            $content instanceof SiteSetting => [
                'fr' => ['now_content' => $content->now_content['fr'] ?? null],
                'en' => ['now_content' => $content->now_content['en'] ?? null],
            ],
            $content instanceof Celebration => [
                'fr' => [
                    ...$this->celebrationTranslations($content, 'fr'),
                    ...collect(self::CELEBRATION_FIELDS)->mapWithKeys(fn (string $field): array => [
                        $field => $content->getAttribute($field) instanceof Carbon ? $content->getAttribute($field)->toDateString() : $content->getAttribute($field),
                    ])->all(),
                ],
                'en' => $this->celebrationTranslations($content, 'en'),
            ],
            $content instanceof Project => ['fr' => $this->projects->toInput($content), 'en' => $this->projects->english($content)],
            $content instanceof Post => ['fr' => $this->posts->toInput($content), 'en' => $this->posts->english($content)],
            default => ['fr' => $this->contents->toInput($type, $content), 'en' => $this->contents->english($type, $content)],
        };
    }

    /**
     * Enregistre une révision en attente, après l'avoir validée comme si elle était appliquée.
     *
     * @param  array<string, mixed>  $french
     * @param  array<string, mixed>  $english
     *
     * @throws ValidationException
     */
    public function propose(string $type, ?int $id, array $french, array $english, string $summary, ?string $draftedBy): ContentRevision
    {
        $content = $this->find($type, $id);
        $fail = fn (string $message) => throw ValidationException::withMessages(['changes' => $message]);

        if ($content === null) {
            $fail('Contenu introuvable.');
        }

        if (in_array($content->getAttribute('status'), [ProjectStatus::Draft, PublicationStatus::Draft], true)) {
            $fail('Ce contenu est un brouillon : modifie-le directement avec les outils de brouillon.');
        }

        $current = $this->snapshot($type, $content);
        $unknown = [...array_diff(array_keys($french), array_keys($current['fr'])), ...array_diff(array_keys($english), array_keys($current['en']))];

        if ($unknown !== []) {
            $fail('Champs inconnus pour ce contenu : '.implode(', ', array_unique($unknown)).'.');
        }

        if ($french === [] && $english === []) {
            $fail('Aucun changement proposé.');
        }

        $merged = [...$current['fr'], ...$french];
        $this->validateFrench($type, $content, $merged);
        $this->validateEnglish($current, $merged, $french, $english);

        return ContentRevision::query()->create([
            'content_type' => $type,
            'content_id' => $type === self::NOW_PAGE ? null : $content->getKey(),
            'changes' => [
                'fr' => $french,
                'en' => $english,
                'before' => [
                    'fr' => array_intersect_key($current['fr'], $french),
                    'en' => array_intersect_key($current['en'], $english),
                ],
            ],
            'summary' => $summary,
            'drafted_by' => $draftedBy,
            'status' => RevisionStatus::Pending,
        ]);
    }

    /**
     * Enregistre une révision d'image en attente pour un projet ou un article publié : la
     * nouvelle couverture (qui remplacera l'ancienne) ou une image ajoutée à la galerie d'un projet.
     *
     * @throws ValidationException
     */
    public function proposeImage(Project|Post $content, string $role, string $bytes, string $extension, string $summary, ?string $draftedBy): ContentRevision
    {
        $type = $content instanceof Project ? 'project' : 'post';

        if (! in_array($role, self::IMAGE_ROLES[$type], true)) {
            throw ValidationException::withMessages(['role' => 'Rôle d\'image inconnu pour ce contenu.']);
        }

        if (in_array($content->status, [ProjectStatus::Draft, PublicationStatus::Draft], true)) {
            throw ValidationException::withMessages(['changes' => 'Ce contenu est un brouillon : ajoute l\'image directement.']);
        }

        return DB::transaction(function () use ($content, $type, $role, $bytes, $extension, $summary, $draftedBy): ContentRevision {
            $revision = ContentRevision::query()->create([
                'content_type' => $type,
                'content_id' => $content->getKey(),
                'changes' => ['fr' => [], 'en' => [], 'image' => ['role' => $role], 'before' => ['fr' => [], 'en' => []]],
                'summary' => $summary,
                'drafted_by' => $draftedBy,
                'status' => RevisionStatus::Pending,
            ]);

            $revision->addMediaFromString($bytes)
                ->usingFileName("{$content->slug}-{$role}-".Str::random(6).".{$extension}")
                ->toMediaCollection('image');

            return $revision;
        });
    }

    /**
     * Applique la révision au contenu tel qu'il est maintenant (les champs non concernés
     * gardent leurs modifications éventuelles depuis la proposition).
     *
     * @throws ValidationException
     */
    public function apply(ContentRevision $revision): void
    {
        $type = $revision->content_type;
        $content = $this->find($type, $revision->content_id);

        if ($content === null) {
            throw ValidationException::withMessages(['revision' => __('Le contenu de cette révision n\'existe plus.')]);
        }

        $french = $revision->changes['fr'] ?? [];
        $english = $revision->changes['en'] ?? [];

        DB::transaction(function () use ($type, $content, $french, $english, $revision): void {
            if (isset($revision->changes['image']) && $content instanceof HasMedia) {
                $image = $revision->getFirstMedia('image')
                    ?? throw ValidationException::withMessages(['revision' => __('L\'image de cette révision est introuvable.')]);
                $image->copy($content, $revision->changes['image']['role']);
            } elseif ($content instanceof Celebration) {
                foreach (self::CELEBRATION_TRANSLATABLE as $field) {
                    foreach (['fr' => $french, 'en' => $english] as $locale => $changes) {
                        if (array_key_exists($field, $changes)) {
                            $content->setTranslation($field, $locale, $changes[$field]);
                        }
                    }
                }

                $content->fill(array_intersect_key($french, array_flip(self::CELEBRATION_FIELDS)))->save();
            } elseif ($content instanceof SiteSetting) {
                $content->updateNowContent([
                    'fr' => $french['now_content'] ?? ($content->now_content['fr'] ?? null),
                    'en' => $english['now_content'] ?? ($content->now_content['en'] ?? null),
                ]);
            } else {
                $current = $this->snapshot($type, $content);

                if ($french !== []) {
                    match (true) {
                        $content instanceof Project => $this->projects->save($content, [...$current['fr'], ...$french]),
                        $content instanceof Post => $this->posts->save($content, [...$current['fr'], ...$french]),
                        default => $this->contents->save($type, $content, [...$current['fr'], ...$french]),
                    };
                }

                if ($english !== []) {
                    match (true) {
                        $content instanceof Project => $this->projects->setEnglish($content->refresh(), $english),
                        $content instanceof Post => $this->posts->setEnglish($content->refresh(), $english),
                        default => $this->contents->setEnglish($type, $content->refresh(), $english),
                    };
                }
            }

            $revision->update(['status' => RevisionStatus::Applied, 'decided_at' => now()]);
        });
    }

    /**
     * La révision prête à afficher : contenu visé et, en détail, chaque champ modifié avec sa
     * valeur au moment de la proposition, la valeur proposée et la valeur actuelle (qui
     * diffère de la première si le contenu a été modifié entre-temps).
     *
     * @return array<string, mixed>
     */
    public function present(ContentRevision $revision, bool $detailed = false): array
    {
        $type = $revision->content_type;
        $content = $this->find($type, $revision->content_id);
        $summary = [
            'id' => $revision->id,
            'content_type' => $type,
            'content_type_label' => self::TYPE_LABELS[$type] ?? $type,
            'content_id' => $revision->content_id,
            'title' => $this->title($type, $content),
            'edit_url' => $this->editUrl($type, $content),
            'summary' => $revision->summary,
            'drafted_by' => $revision->drafted_by,
            'status' => $revision->status->value,
            'created_at' => $revision->created_at?->toIso8601String(),
            'decided_at' => $revision->decided_at?->toIso8601String(),
        ];

        if (! $detailed) {
            return $summary;
        }

        $image = null;

        if (isset($revision->changes['image'])) {
            $role = $revision->changes['image']['role'];
            $image = [
                'role' => $role,
                'url' => $revision->getFirstMediaUrl('image') ?: null,
                'current_url' => $role === 'cover' && $content instanceof HasMedia ? ($content->getFirstMediaUrl('cover') ?: null) : null,
            ];
        }

        $current = $content === null ? ['fr' => [], 'en' => []] : $this->snapshot($type, $content);
        $fields = [];

        foreach (['fr', 'en'] as $locale) {
            foreach ($revision->changes[$locale] ?? [] as $field => $after) {
                $fields[] = [
                    'field' => $field,
                    'locale' => $locale,
                    'before' => $revision->changes['before'][$locale][$field] ?? null,
                    'after' => $after,
                    'current' => $current[$locale][$field] ?? null,
                ];
            }
        }

        return [...$summary, 'fields' => $fields, 'image' => $image];
    }

    /**
     * Collections qu'une révision d'image peut remplir, par type de contenu.
     *
     * @var array<string, list<string>>
     */
    private const array IMAGE_ROLES = ['project' => ['cover', 'gallery'], 'post' => ['cover']];

    /** @var array<string, string> */
    private const array TYPE_LABELS = [
        'project' => 'Projet',
        'post' => 'Article',
        'experience' => 'Expérience',
        'education' => 'Formation',
        'certification' => 'Certification',
        'distinction' => 'Distinction',
        'uses_item' => 'Page « Uses »',
        'skill' => 'Compétence',
        'service' => 'Offre',
        self::CELEBRATION => 'Surprise',
        self::NOW_PAGE => 'Page « Now »',
    ];

    /**
     * Le message et le bouton d'une surprise dans une langue.
     *
     * @return array<string, string|null>
     */
    private function celebrationTranslations(Celebration $celebration, string $locale): array
    {
        return collect(self::CELEBRATION_TRANSLATABLE)
            ->mapWithKeys(fn (string $field): array => [$field => $celebration->getTranslation($field, $locale, false) ?: null])
            ->all();
    }

    public function reject(ContentRevision $revision): void
    {
        $revision->update(['status' => RevisionStatus::Rejected, 'decided_at' => now()]);
    }

    /**
     * @param  array<string, mixed>  $merged
     *
     * @throws ValidationException
     */
    private function validateFrench(string $type, Model $content, array $merged): void
    {
        $rules = match (true) {
            $content instanceof SiteSetting => ['now_content' => ['nullable', 'string', 'max:5000']],
            $content instanceof Celebration => Celebration::DRAFT_RULES,
            $content instanceof Project => $this->projects->rules($content),
            $content instanceof Post => $this->posts->rules($content),
            default => $this->contents->rules($type),
        };

        if ($content instanceof Project) {
            $merged['github_repository'] = ProjectDraftWriter::repositoryName($merged['github_repository'] ?? null);
        }

        Validator::make($merged, $rules)->validate();
    }

    /**
     * Le contenu est en ligne dans les deux langues : un champ traduit modifié en français doit
     * l'être aussi en anglais, et une liste traduite garder le même nombre d'éléments.
     *
     * @param  array{fr: array<string, mixed>, en: array<string, mixed>}  $current
     * @param  array<string, mixed>  $merged
     * @param  array<string, mixed>  $french
     * @param  array<string, mixed>  $english
     *
     * @throws ValidationException
     */
    private function validateEnglish(array $current, array $merged, array $french, array $english): void
    {
        $missing = [];

        foreach (array_keys($french) as $field) {
            $englishField = self::ENGLISH_LISTS[$field] ?? $field;

            if (array_key_exists($englishField, $current['en']) && filled($french[$field]) && ! array_key_exists($englishField, $english)) {
                $missing[] = $englishField;
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages(['en' => 'Contenu en ligne en deux langues : fournis aussi l\'anglais de '.implode(', ', $missing).'.']);
        }

        foreach (self::ENGLISH_LISTS as $frenchList => $englishList) {
            if (array_key_exists($englishList, $english) && count((array) $english[$englishList]) !== count((array) ($merged[$frenchList] ?? []))) {
                throw ValidationException::withMessages(['en' => "L'anglais de {$englishList} doit compter autant d'éléments que le français, dans le même ordre."]);
            }
        }
    }
}

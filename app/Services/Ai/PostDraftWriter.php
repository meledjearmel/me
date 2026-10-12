<?php

namespace App\Services\Ai;

use App\Enums\PublicationStatus;
use App\Jobs\TranslatePostTag;
use App\Models\Post;
use App\Models\PostSeries;
use App\Models\PostTag;
use App\Services\PostMentions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Écriture des brouillons d'articles par le serveur MCP : en français à la création, en
 * anglais une fois le français validé. Un article publié n'est jamais modifié ici.
 */
class PostDraftWriter
{
    public function __construct(private readonly PostMentions $mentions) {}

    /**
     * L'article en français dans la forme d'entrée de save-post-draft.
     *
     * @return array<string, mixed>
     */
    public function toInput(Post $post): array
    {
        $post->loadMissing(['tags', 'series']);

        return [
            'title' => $post->getTranslation('title', 'fr', false) ?: null,
            'slug' => $post->slug,
            'excerpt' => $post->getTranslation('excerpt', 'fr', false) ?: null,
            'body' => $post->getTranslation('body', 'fr', false) ?: null,
            'tags' => $post->tags->map(fn (PostTag $tag): string => $tag->getTranslation('name', 'fr'))->values()->all(),
            'series' => $post->series?->getTranslation('name', 'fr'),
            'series_position' => $post->series_position,
        ];
    }

    /**
     * Valide puis enregistre le brouillon en français ; renvoie ce que l'agent doit savoir
     * (mentions retirées, place déjà prise dans la série).
     *
     * @param  array<string, mixed>  $input
     * @return list<string>
     *
     * @throws ValidationException
     */
    public function save(Post $post, array $input): array
    {
        $data = Validator::make($input, [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'not_in:feed', Rule::unique('posts', 'slug')->ignore($post->id)],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string'],
            'tags' => ['nullable', 'array', 'max:8'],
            'tags.*' => ['string', 'max:40'],
            'series' => ['nullable', 'string', 'max:80'],
            'series_position' => ['nullable', 'integer', 'min:1', 'max:99', 'required_with:series'],
        ])->validate();

        ['html' => $body, 'removed' => $removed] = $this->withEditorMentions($data['body']);

        DB::transaction(function () use ($post, $data, $body): void {
            $post->setTranslation('title', 'fr', $data['title']);
            $post->setTranslation('body', 'fr', $body);
            filled($data['excerpt'] ?? null)
                ? $post->setTranslation('excerpt', 'fr', PostMentions::plain($data['excerpt']))
                : $post->forgetTranslation('excerpt', 'fr');

            $post->fill([
                'slug' => $data['slug'],
                'status' => $post->status ?? PublicationStatus::Draft,
                'post_series_id' => filled($data['series'] ?? null) ? $this->seriesId($data['series']) : null,
                'series_position' => filled($data['series'] ?? null) ? $data['series_position'] : null,
            ])->save();

            $post->tags()->sync($this->tagIds($data['tags'] ?? []));
        });

        $notes = [];

        if ($removed !== []) {
            $notes[] = 'Mentions retirées (élément inconnu ou non publié, remplacé par son libellé) : '.implode(', ', $removed);
        }

        if ($post->post_series_id !== null) {
            $sibling = Post::query()
                ->where('post_series_id', $post->post_series_id)
                ->where('series_position', $post->series_position)
                ->whereKeyNot($post->id)
                ->first();

            $notes[] = $sibling === null
                ? "Placé en partie {$post->series_position} de la série « {$post->series->getTranslation('name', 'fr')} »."
                : "Attention : la partie {$post->series_position} de la série est déjà « {$sibling->getTranslation('title', 'fr')} » (#{$sibling->id}). Demande au développeur la bonne place.";
        }

        return $notes;
    }

    /**
     * Enregistre l'anglais d'un brouillon, sans toucher au français.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function saveTranslation(Post $post, array $input): void
    {
        $data = Validator::make($input, [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string'],
        ])->validate();

        $post->setTranslation('title', 'en', $data['title']);
        $post->setTranslation('body', 'en', $this->withEditorMentions($data['body'])['html']);

        if (filled($data['excerpt'] ?? null)) {
            $post->setTranslation('excerpt', 'en', PostMentions::plain($data['excerpt']));
        }

        $post->save();
    }

    /**
     * Change les jetons « @[Libellé](type:id) » en mentions de l'éditeur ; ceux d'un
     * élément inconnu ou non publié redeviennent leur libellé.
     *
     * @return array{html: string, removed: list<string>}
     */
    private function withEditorMentions(string $html): array
    {
        ['text' => $html, 'removed' => $removed] = $this->mentions->withoutUnknownMentions($html);

        $html = (string) preg_replace_callback(PostMentions::TOKEN, function (array $match): string {
            $label = e($match[1]);

            return "<span data-type=\"mention\" data-kind=\"{$match[2]}\" data-id=\"{$match[3]}\" data-label=\"{$label}\">@{$label}</span>";
        }, $html);

        return ['html' => $html, 'removed' => array_values(array_unique($removed))];
    }

    /**
     * Les tags par leur nom, créés au besoin (même règle que l'administration).
     *
     * @param  array<int, string>  $names
     * @return array<int, int>
     */
    private function tagIds(array $names): array
    {
        return collect($names)
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->unique(fn (string $name): string => str($name)->slug()->toString())
            ->map(function (string $name): int {
                $tag = PostTag::query()->firstOrCreate(
                    ['slug' => str($name)->slug()->toString()],
                    ['name' => ['fr' => $name, 'en' => $name]],
                );

                if ($tag->wasRecentlyCreated) {
                    TranslatePostTag::dispatch($tag);
                }

                return $tag->id;
            })
            ->values()
            ->all();
    }

    private function seriesId(string $name): int
    {
        return PostSeries::query()->firstOrCreate(
            ['slug' => str(trim($name))->slug()->toString()],
            ['name' => ['fr' => trim($name), 'en' => trim($name)]],
        )->id;
    }
}

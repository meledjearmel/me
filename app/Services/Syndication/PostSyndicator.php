<?php

namespace App\Services\Syndication;

use App\Enums\SyndicationMode;
use App\Enums\SyndicationPlatform;
use App\Jobs\SendPushNotification;
use App\Models\Post;
use App\Models\PostSyndication;
use App\Models\PostTag;
use App\Models\SiteSetting;
use App\Services\HtmlToMarkdown;
use App\Services\PostContent;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Republie un article publié sur dev.to ou Hashnode : en Markdown, liens et images en
 * adresses absolues, lien canonique vers le site, tags et couverture. Une republication par
 * article et plateforme : la relancer met à jour l'article existant. Une erreur est gardée
 * sur la republication (et signalée), jamais levée vers l'appelant.
 */
class PostSyndicator
{
    public function __construct(
        private readonly PostContent $content,
        private readonly HtmlToMarkdown $markdown,
        private readonly DevToClient $devTo,
        private readonly HashnodeClient $hashnode,
    ) {}

    /**
     * Les plateformes prêtes (clé renseignée, mode manuel ou auto).
     *
     * @return list<SyndicationPlatform>
     */
    public function available(): array
    {
        $settings = SiteSetting::current();

        return array_values(array_filter(
            SyndicationPlatform::cases(),
            fn (SyndicationPlatform $platform): bool => $settings->syndicationMode($platform) !== SyndicationMode::Off
                && ($platform !== SyndicationPlatform::Hashnode || filled($settings->hashnode_publication_id)),
        ));
    }

    public function syndicate(Post $post, SyndicationPlatform $platform): PostSyndication
    {
        $settings = SiteSetting::current();
        $locale = $settings->{"{$platform->value}_locale"} ?: 'en';

        // Sans corps dans la langue choisie, on republie le français.
        if (blank($post->getTranslation('body', $locale, false))) {
            $locale = 'fr';
        }

        /** @var PostSyndication $syndication */
        $syndication = $post->syndications()->firstOrNew(['platform' => $platform->value]);
        $syndication->fill(['locale' => $locale, 'status' => PostSyndication::PENDING]);

        try {
            if (! $post->isPublished()) {
                throw new RuntimeException('L\'article doit être publié sur le site avant d\'être republié.');
            }

            if (! in_array($platform, $this->available(), true)) {
                throw new RuntimeException("{$platform->label()} n'est pas configuré (clé, et identifiant de publication pour Hashnode).");
            }

            $article = $this->article($post, $locale);
            $result = match ($platform) {
                SyndicationPlatform::DevTo => $this->devTo->publish((string) $settings->devto_api_key, $article, $syndication->external_id),
                SyndicationPlatform::Hashnode => $this->hashnode->publish((string) $settings->hashnode_api_key, (string) $settings->hashnode_publication_id, $article, $syndication->external_id),
            };

            $syndication->fill([
                'status' => PostSyndication::PUBLISHED,
                'external_id' => $result['id'] ?: $syndication->external_id,
                'external_url' => $result['url'] ?: $syndication->external_url,
                'error' => null,
                'published_at' => $syndication->published_at ?? now(),
            ])->save();
        } catch (\Throwable $exception) {
            $syndication->fill(['status' => PostSyndication::FAILED, 'error' => mb_substr($exception->getMessage(), 0, 1000)])->save();

            SendPushNotification::dispatch(
                "Republication sur {$platform->label()} échouée",
                $post->getTranslation('title', 'fr'),
                ['type' => 'post', 'id' => (string) $post->id],
            );
        }

        return $syndication;
    }

    /**
     * Les articles à republier en mode auto : publiés après l'activation du mode, et pas
     * encore envoyés sur cette plateforme.
     *
     * @return Collection<int, Post>
     */
    public function dueForAuto(SyndicationPlatform $platform): Collection
    {
        $settings = SiteSetting::current();
        $since = $settings->{"{$platform->value}_auto_since"};

        if ($settings->syndicationMode($platform) !== SyndicationMode::Auto || $since === null || ! in_array($platform, $this->available(), true)) {
            return collect();
        }

        return Post::query()
            ->published()
            ->where('published_at', '>=', $since)
            ->whereDoesntHave('syndications', fn ($query) => $query->where('platform', $platform->value))
            ->get();
    }

    /**
     * L'article prêt pour une plateforme.
     *
     * @return array{title: string, markdown: string, description: string|null, canonical_url: string, tags: list<string>, cover_url: string|null}
     */
    public function article(Post $post, string $locale): array
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $html = $this->content->withMentions($post->getTranslation('body', $locale), $locale)['html'];
        $cover = $post->getFirstMediaUrl('cover') ?: null;

        return [
            'title' => $post->getTranslation('title', $locale),
            'markdown' => $this->markdown->convert($html, $baseUrl),
            'description' => $post->getTranslation('excerpt', $locale, false) ?: null,
            'canonical_url' => route('blog.show', ['locale' => $locale, 'post' => $post->slug]),
            'tags' => $post->tags->map(fn (PostTag $tag): string => $tag->getTranslation('name', $locale))->values()->all(),
            'cover_url' => $cover === null ? null : (str_starts_with($cover, 'http') ? $cover : $baseUrl.'/'.ltrim($cover, '/')),
        ];
    }
}

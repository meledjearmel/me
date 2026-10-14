<?php

namespace App\Services\Syndication;

use App\Ai\Agents\SocialPostWriter;
use App\Models\Post;
use App\Services\Ai\TextAssistService;
use App\Services\PostContent;
use Illuminate\Support\Str;

/**
 * Le kit de partage d'un article : un texte par réseau (LinkedIn, X, Facebook, statut
 * WhatsApp) rédigé par l'IA et retouchable, et les liens qui ouvrent chaque réseau avec le
 * texte et l'adresse de l'article déjà remplis.
 */
class SocialKit
{
    public const array NETWORKS = ['linkedin', 'x', 'facebook', 'whatsapp'];

    /** Longueur maximale de chaque texte (X garde la place du lien). */
    private const array LIMITS = ['linkedin' => 2800, 'x' => 255, 'facebook' => 2000, 'whatsapp' => 300];

    public function __construct(
        private readonly TextAssistService $assist,
        private readonly PostContent $content,
    ) {}

    /**
     * Fait rédiger les textes d'une langue et les enregistre. False si aucun fournisseur n'a répondu.
     */
    public function generate(Post $post, string $locale): bool
    {
        $article = "Titre : {$post->getTranslation('title', $locale)}\n"
            .'Résumé : '.($post->getTranslation('excerpt', $locale, false) ?: '—')."\n\n"
            .Str::limit($this->content->text($post->getTranslation('body', $locale)), 6000);

        $texts = $this->assist->promptJson(new SocialPostWriter($locale), $article, fn (array $data): ?array => $this->normalize($data));

        if ($texts === null) {
            return false;
        }

        $this->save($post, $locale, $texts);

        return true;
    }

    /**
     * @param  array<string, mixed>  $texts
     */
    public function save(Post $post, string $locale, array $texts): void
    {
        $all = $post->social_texts ?? [];
        $all[$locale] = [...($all[$locale] ?? []), ...collect($texts)->only(self::NETWORKS)->all()];

        $post->forceFill(['social_texts' => $all])->saveQuietly();
    }

    /**
     * Les textes ramenés aux longueurs des réseaux ; null si aucun n'est exploitable.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>|null
     */
    public function normalize(array $data): ?array
    {
        $texts = collect(self::NETWORKS)
            ->mapWithKeys(fn (string $network): array => [$network => is_string($data[$network] ?? null) && trim($data[$network]) !== ''
                ? mb_substr(trim($data[$network]), 0, self::LIMITS[$network])
                : null])
            ->filter()
            ->all();

        return $texts === [] ? null : $texts;
    }

    /**
     * Les liens de partage d'une langue : chaque réseau s'ouvre avec le texte et l'adresse.
     * Facebook n'accepte que l'adresse (le texte est à coller) ; le statut WhatsApp passe par le
     * partage natif du téléphone avec l'image de l'article.
     *
     * @return array{url: string, image: string, texts: array<string, string|null>, links: array<string, string>}
     */
    public function kit(Post $post, string $locale): array
    {
        $url = route('blog.show', ['locale' => $locale, 'post' => $post->slug]);
        $texts = collect(self::NETWORKS)->mapWithKeys(fn (string $network): array => [$network => $post->social_texts[$locale][$network] ?? null])->all();
        $title = $post->getTranslation('title', $locale);

        return [
            'url' => $url,
            'image' => route('blog.share-image', ['locale' => $locale, 'post' => $post->slug]),
            'texts' => $texts,
            'links' => [
                'linkedin' => 'https://www.linkedin.com/feed/?shareActive=true&text='.rawurlencode(($texts['linkedin'] ?? $title)."\n\n".$url),
                'x' => 'https://x.com/intent/post?text='.rawurlencode($texts['x'] ?? $title).'&url='.rawurlencode($url),
                'facebook' => 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($url),
                'whatsapp' => 'https://wa.me/?text='.rawurlencode(($texts['whatsapp'] ?? $title).' '.$url),
            ],
        ];
    }
}

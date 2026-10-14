<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SyndicationPlatform;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SocialTextsRequest;
use App\Http\Requests\Admin\SyndicationSettingsRequest;
use App\Models\Post;
use App\Models\PostSyndication;
use App\Services\Syndication\PostSyndicator;
use App\Services\Syndication\SocialKit;
use App\Services\Syndication\SyndicationSettings;
use Illuminate\Http\JsonResponse;

/**
 * @tags Republication
 */
class PostSyndicationController extends Controller
{
    /**
     * Réglages de republication
     *
     * Pour `devto` et `hashnode` : le mode (`off`, `manual` : bouton de la fiche, `auto` : chaque
     * article publié après `auto_since` part tout seul), la présence d'une clé (jamais la clé
     * elle-même), la langue republiée et, pour Hashnode, l'identifiant de la publication.
     *
     * @response array<string, array{mode: string, has_key: bool, locale: string, auto_since: string|null, publication_id?: string|null}>
     */
    public function settings(SyndicationSettings $settings): JsonResponse
    {
        return response()->json($settings->toArray());
    }

    /**
     * Modifier les réglages de republication
     *
     * Une clé vide garde la clé enregistrée ; `devto_clear_key` / `hashnode_clear_key` la retire.
     * Passer en `auto` note la date d'activation : les articles plus anciens ne partent pas seuls.
     * Sans `hashnode_publication_id`, la première publication du compte Hashnode est retrouvée
     * avec le jeton ; `hashnode_error` dit pourquoi si ce n'est pas possible.
     *
     * @response array{devto: array<string, mixed>, hashnode: array<string, mixed>, hashnode_error: string|null}
     */
    public function updateSettings(SyndicationSettingsRequest $request, SyndicationSettings $settings): JsonResponse
    {
        $error = $settings->update($request->validated());

        return response()->json([...$settings->toArray(), 'hashnode_error' => $error]);
    }

    /**
     * Publications Hashnode du compte
     *
     * Les blogs du compte du jeton Hashnode enregistré, pour choisir `hashnode_publication_id`
     * (retrouvé tout seul à l'enregistrement s'il est vide). Vide sans jeton.
     *
     * @response list<array{id: string, title: string, url: string}>
     */
    public function hashnodePublications(SyndicationSettings $settings): JsonResponse
    {
        return response()->json($settings->hashnodePublications());
    }

    /**
     * Republication et kit de partage d'un article
     *
     * Où en est l'article sur chaque plateforme (`status` : `pending`, `published`, `failed`
     * avec `error`), et pour chaque langue son kit de partage : textes par réseau et liens qui
     * ouvrent LinkedIn, X, Facebook et WhatsApp déjà remplis.
     *
     * @response array{syndications: list<array{platform: string, status: string, external_url: string|null, locale: string, error: string|null, published_at: string|null}>, social: array<string, array{url: string, image: string, texts: array<string, string|null>, links: array<string, string>}>}
     */
    public function show(Post $post, SocialKit $kit): JsonResponse
    {
        return response()->json([
            'syndications' => $post->syndications()->get(['platform', 'status', 'external_url', 'locale', 'error', 'published_at']),
            'social' => collect(['fr', 'en'])
                ->filter(fn (string $locale): bool => filled($post->getTranslation('title', $locale, false)))
                ->mapWithKeys(fn (string $locale): array => [$locale => $kit->kit($post, $locale)])
                ->all(),
        ]);
    }

    /**
     * Republier un article
     *
     * Publie l'article (déjà publié sur le site) sur `devto` ou `hashnode`, ou met à jour la
     * version déjà republiée, avec un lien canonique vers le site. 422 avec l'erreur de la
     * plateforme en cas d'échec.
     *
     * @response array{platform: string, status: string, external_url: string|null, error: string|null}
     */
    public function store(Post $post, string $platform, PostSyndicator $syndicator): JsonResponse
    {
        $target = SyndicationPlatform::tryFrom($platform);
        abort_if($target === null, 404);

        $syndication = $syndicator->syndicate($post->load('tags'), $target);

        return response()->json(
            $syndication->only(['platform', 'status', 'external_url', 'error']),
            $syndication->status === PostSyndication::PUBLISHED ? 200 : 422,
        );
    }

    /**
     * Faire rédiger le kit de partage par l'IA
     *
     * Textes pour LinkedIn, X, Facebook et le statut WhatsApp dans la langue demandée
     * (`fr` ou `en`), enregistrés sur l'article.
     *
     * @response array{url: string, image: string, texts: array<string, string|null>, links: array<string, string>}
     * @response 503 array{message: string}
     */
    public function generateSocialTexts(Post $post, string $locale, SocialKit $kit): JsonResponse
    {
        abort_unless(in_array($locale, ['fr', 'en'], true), 404);

        if (! $kit->generate($post, $locale)) {
            return response()->json(['message' => __('L\'assistance IA est momentanément indisponible.')], 503);
        }

        return response()->json($kit->kit($post->refresh(), $locale));
    }

    /**
     * Modifier le kit de partage
     *
     * @response array{url: string, image: string, texts: array<string, string|null>, links: array<string, string>}
     */
    public function updateSocialTexts(SocialTextsRequest $request, Post $post, SocialKit $kit): JsonResponse
    {
        $kit->save($post, $request->validated('locale'), $request->validated('texts'));

        return response()->json($kit->kit($post->refresh(), $request->validated('locale')));
    }
}

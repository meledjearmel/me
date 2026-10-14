<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SyndicationPlatform;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SocialTextsRequest;
use App\Models\Post;
use App\Models\PostSyndication;
use App\Services\Syndication\PostSyndicator;
use App\Services\Syndication\SocialKit;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Depuis la fiche d'un article : le republier (ou le mettre à jour) sur dev.to ou Hashnode,
 * et préparer son kit de partage (textes par réseau, rédigés par l'IA puis retouchés).
 */
class PostSyndicationController extends Controller
{
    public function store(Post $post, string $platform, PostSyndicator $syndicator): RedirectResponse
    {
        $target = SyndicationPlatform::tryFrom($platform);
        abort_if($target === null, 404);

        $syndication = $syndicator->syndicate($post->load('tags'), $target);

        Inertia::flash('toast', $syndication->status === PostSyndication::PUBLISHED
            ? ['type' => 'success', 'message' => __('Article publié sur :platform.', ['platform' => $target->label()])]
            : ['type' => 'error', 'message' => (string) $syndication->error]);

        return back();
    }

    public function generateSocialTexts(Post $post, string $locale, SocialKit $kit): RedirectResponse
    {
        abort_unless(in_array($locale, ['fr', 'en'], true), 404);

        Inertia::flash('toast', $kit->generate($post, $locale)
            ? ['type' => 'success', 'message' => __('Textes de partage rédigés : relisez-les avant de publier.')]
            : ['type' => 'error', 'message' => __('L\'assistance IA est momentanément indisponible.')]);

        return back();
    }

    public function updateSocialTexts(SocialTextsRequest $request, Post $post, SocialKit $kit): RedirectResponse
    {
        $kit->save($post, $request->validated('locale'), $request->validated('texts'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Textes de partage enregistrés.')]);

        return back();
    }
}

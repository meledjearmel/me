<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\SiteSetting;
use App\Services\ShareImage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Image de partage générée d'un article publié (utilisée quand il n'a pas de couverture).
 */
class PostShareImageController extends Controller
{
    public function __invoke(ShareImage $images, string $locale, Post $post): BinaryFileResponse
    {
        abort_unless(SiteSetting::current()->blog_enabled && $post->isPublished(), HttpResponse::HTTP_NOT_FOUND);

        return response()->file($images->forPost($post, $locale), [
            'Content-Type' => 'image/png',
            // Le contenu ne change qu'avec le titre ; les réseaux la gardent un jour.
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}

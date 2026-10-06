<?php

namespace App\Http\Controllers;

use App\Enums\PostShareNetwork;
use App\Models\Post;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PostShareController extends Controller
{
    /** Compte un partage de l'article et renvoie le total à jour. */
    public function __invoke(Request $request, string $locale, Post $post): JsonResponse
    {
        abort_unless(SiteSetting::current()->blog_enabled && $post->isPublished(), Response::HTTP_NOT_FOUND);

        $validated = $request->validate([
            'network' => ['required', Rule::enum(PostShareNetwork::class)],
        ]);

        $post->recordShare($request, PostShareNetwork::from($validated['network']));

        return response()->json(['total' => $post->sharesTotal()]);
    }
}

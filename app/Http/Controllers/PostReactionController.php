<?php

namespace App\Http\Controllers;

use App\Enums\PostReactionType;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Services\PostReactions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PostReactionController extends Controller
{
    public function __construct(private PostReactions $reactions) {}

    /** Ajoute ou retire la réaction du lecteur, et renvoie les compteurs à jour. */
    public function __invoke(Request $request, string $locale, Post $post): JsonResponse
    {
        $settings = SiteSetting::current();
        abort_unless($settings->blog_enabled && $settings->blog_reactions_enabled && $post->isPublished(), Response::HTTP_NOT_FOUND);

        $validated = $request->validate([
            'type' => ['required', Rule::enum(PostReactionType::class)],
        ]);

        $reader = $this->reactions->ensureReaderHash($request);
        $this->reactions->toggle($post, PostReactionType::from($validated['type']), $reader);

        return response()->json($this->reactions->summary($post, $reader));
    }
}

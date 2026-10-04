<?php

namespace App\Http\Controllers;

use App\Enums\CommentStatus;
use App\Http\Requests\PostCommentRequest;
use App\Jobs\SendPushNotification;
use App\Models\Post;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class PostCommentController extends Controller
{
    /** Reçoit le commentaire d'un lecteur, en attente de modération. */
    public function store(PostCommentRequest $request, string $locale, Post $post): RedirectResponse
    {
        $settings = SiteSetting::current();
        abort_unless($settings->blog_enabled && $settings->blog_comments_enabled && $post->isPublished(), Response::HTTP_NOT_FOUND);

        $comment = $post->comments()->create([
            ...$request->safe()->except('website'),
            'locale' => $locale,
            'status' => CommentStatus::Pending,
        ]);

        SendPushNotification::dispatch('Nouveau commentaire', "{$comment->author_name} · {$post->getTranslation('title', 'fr')}", ['type' => 'post_comment', 'id' => (string) $comment->id]);

        return back();
    }
}

<?php

namespace App\Jobs;

use App\Enums\SyndicationPlatform;
use App\Models\Post;
use App\Services\Syndication\PostSyndicator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Republie un article sur une plateforme en tâche de fond. Un seul essai : un second
 * passage après une réponse perdue pourrait créer un doublon ; l'erreur reste sur la fiche.
 */
class SyndicatePost implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 1;

    public function __construct(public int $postId, public string $platform) {}

    public function handle(PostSyndicator $syndicator): void
    {
        $post = Post::query()->with('tags')->find($this->postId);
        $platform = SyndicationPlatform::tryFrom($this->platform);

        if ($post !== null && $platform !== null) {
            $syndicator->syndicate($post, $platform);
        }
    }
}

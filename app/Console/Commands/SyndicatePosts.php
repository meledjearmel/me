<?php

namespace App\Console\Commands;

use App\Enums\SyndicationPlatform;
use App\Jobs\SyndicatePost;
use App\Models\PostSyndication;
use App\Services\Syndication\PostSyndicator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Republie les articles en mode auto : ceux publiés depuis l'activation du mode et pas encore
 * envoyés, y compris les articles programmés dont la date vient de passer. Planifiée
 * régulièrement ; un article ne part qu'une fois par plateforme.
 */
#[Signature('posts:syndicate')]
#[Description('Republie sur dev.to et Hashnode les articles publiés, pour les plateformes en mode auto')]
class SyndicatePosts extends Command
{
    public function handle(PostSyndicator $syndicator): int
    {
        $count = 0;

        foreach (SyndicationPlatform::cases() as $platform) {
            foreach ($syndicator->dueForAuto($platform) as $post) {
                // Réservée tout de suite : un second passage ne la renverrait pas.
                $post->syndications()->create(['platform' => $platform->value, 'status' => PostSyndication::PENDING]);
                SyndicatePost::dispatch($post->id, $platform->value);
                $count++;
            }
        }

        $this->info("{$count} republication(s) lancée(s).");

        return self::SUCCESS;
    }
}

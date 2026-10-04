<?php

namespace App\Console\Commands;

use App\Mail\NewPostMail;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Models\Subscriber;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Annonce aux abonnés les articles en ligne qui ne leur ont pas encore été envoyés,
 * y compris les articles programmés dont la date vient de passer. Planifiée
 * régulièrement ; un article n'est envoyé qu'une fois.
 */
#[Signature('newsletter:send')]
#[Description('Envoie les nouveaux articles du blog aux abonnés de la newsletter')]
class SendNewsletter extends Command
{
    public function handle(): int
    {
        if (! SiteSetting::current()->blog_enabled) {
            return self::SUCCESS;
        }

        $posts = Post::query()->published()->whereNull('newsletter_sent_at')->oldest('published_at')->get();

        foreach ($posts as $post) {
            $post->forceFill(['newsletter_sent_at' => now()])->saveQuietly();

            Subscriber::query()->active()->each(function (Subscriber $subscriber) use ($post): void {
                Mail::to($subscriber->email)->queue(new NewPostMail($post, $subscriber));
            });
        }

        $this->info("{$posts->count()} article(s) envoyé(s).");

        return self::SUCCESS;
    }
}

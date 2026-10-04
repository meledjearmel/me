<?php

namespace App\Mail;

use App\Models\Post;
use App\Models\Profile;
use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

/**
 * Annonce d'un nouvel article à un abonné de la newsletter, dans sa langue
 * (repli sur le français si l'article n'est pas traduit).
 */
class NewPostMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(public Post $post, public Subscriber $subscriber)
    {
        $this->locale($subscriber->locale);
    }

    public function envelope(): Envelope
    {
        $profile = Profile::query()->first(['name', 'email']);

        return new Envelope(
            subject: $this->post->getTranslation('title', $this->locale),
            replyTo: $profile?->email ? [new Address($profile->email, $profile->name ?? config('app.name'))] : [],
        );
    }

    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => "<{$this->subscriber->unsubscribeUrl()}>",
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.new-post',
            with: [
                'isEnglish' => $this->locale === 'en',
                'ownerName' => Profile::query()->value('name') ?? config('app.name'),
                'title' => $this->post->getTranslation('title', $this->locale),
                'excerpt' => $this->post->getTranslation('excerpt', $this->locale),
                'readingMinutes' => $this->post->reading_minutes,
                'postUrl' => rtrim((string) config('app.url'), '/')."/{$this->locale}/blog/{$this->post->slug}",
                'unsubscribeUrl' => $this->subscriber->unsubscribeUrl(),
            ],
        );
    }
}

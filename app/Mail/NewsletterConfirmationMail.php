<?php

namespace App\Mail;

use App\Models\Profile;
use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Lien de confirmation envoyé après une inscription à la newsletter (double opt-in).
 */
class NewsletterConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(public Subscriber $subscriber)
    {
        $this->locale($subscriber->locale);
    }

    public function envelope(): Envelope
    {
        $owner = Profile::query()->value('name') ?? config('app.name');

        return new Envelope(
            subject: $this->locale === 'en'
                ? "Confirm your subscription — {$owner}"
                : "Confirmez votre inscription — {$owner}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.newsletter-confirmation',
            with: [
                'isEnglish' => $this->locale === 'en',
                'ownerName' => Profile::query()->value('name') ?? config('app.name'),
                'confirmUrl' => $this->subscriber->confirmUrl(),
            ],
        );
    }
}

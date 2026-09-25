<?php

namespace App\Mail;

use App\Models\Engagement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Notification envoyée au propriétaire du site quand une demande de
 * collaboration (projet freelance ou recrutement) arrive.
 */
class EngagementReceivedMail extends Mailable
{
    use Queueable;

    public function __construct(public Engagement $engagement) {}

    public function envelope(): Envelope
    {
        $type = $this->engagement->type->value === 'hiring' ? 'Recrutement' : 'Projet freelance';

        return new Envelope(
            subject: "{$type} : {$this->engagement->name}",
            replyTo: [new Address($this->engagement->email, $this->engagement->name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.engagement-received',
            with: ['engagement' => $this->engagement->loadMissing('jobProfile')],
        );
    }
}

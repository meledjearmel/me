<?php

namespace App\Mail;

use App\Models\Profile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Accusé de réception envoyé au visiteur qui a écrit depuis le site (message
 * de contact, projet freelance, recrutement sans CV joint).
 *
 * Il part vers une adresse saisie par le visiteur : pour ne pas servir de
 * relais à du spam, il ne reprend aucun texte libre de sa demande, seulement
 * son nom.
 */
class VisitorAcknowledgementMail extends Mailable implements ShouldQueue
{
    use Queueable;

    /**
     * @param  'contact'|'freelance'|'hiring'  $kind
     */
    public function __construct(
        public string $visitorName,
        public string $kind,
        string $locale,
    ) {
        $this->locale($locale);
    }

    public function envelope(): Envelope
    {
        $profile = Profile::query()->first(['name', 'email']);
        $owner = $profile?->name ?? config('app.name');

        return new Envelope(
            subject: $this->locale === 'en'
                ? "Message received — {$owner}"
                : "Message bien reçu — {$owner}",
            // Si le visiteur répond, sa réponse arrive au propriétaire.
            replyTo: $profile?->email ? [new Address($profile->email, $owner)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.visitor-acknowledgement',
            with: [
                'isEnglish' => $this->locale === 'en',
                'ownerName' => Profile::query()->value('name') ?? config('app.name'),
                'projectsUrl' => rtrim((string) config('app.url'), '/')."/{$this->locale}/projects",
            ],
        );
    }
}

<?php

namespace App\Mail;

use App\Models\Engagement;
use App\Models\Profile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Le CV adapté au poste, envoyé au recruteur qui l'a demandé depuis le site.
 */
class CvMail extends Mailable
{
    use Queueable;

    public function __construct(
        public Engagement $engagement,
        public string $pdf,
        public string $filename,
        public string $ownerName,
    ) {
        $this->locale($engagement->locale);
    }

    public function envelope(): Envelope
    {
        $email = Profile::query()->value('email');

        return new Envelope(
            subject: $this->engagement->locale === 'en'
                ? "My CV — {$this->ownerName}"
                : "Mon CV — {$this->ownerName}",
            // Une réponse du recruteur arrive directement dans la boîte du propriétaire.
            replyTo: $email ? [new Address($email, $this->ownerName)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.cv',
            with: [
                'isEnglish' => $this->engagement->locale === 'en',
                'recruiter' => $this->engagement->name,
                'position' => $this->engagement->subject,
                'ownerName' => $this->ownerName,
                'filename' => $this->filename,
                'phone' => Profile::query()->value('phone'),
                'projectsUrl' => rtrim((string) config('app.url'), '/')."/{$this->engagement->locale}/projects",
            ],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => $this->pdf, $this->filename)->withMime('application/pdf'),
        ];
    }
}

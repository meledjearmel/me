<?php

namespace App\Mail;

use App\Models\Engagement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
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
        return new Envelope(
            subject: $this->engagement->locale === 'en'
                ? "My CV — {$this->ownerName}"
                : "Mon CV — {$this->ownerName}",
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

<?php

namespace App\Mail;

use App\Models\CommercialDocument;
use App\Models\Profile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Un devis, une proforma ou une facture envoyé au client, avec le message relu par le
 * développeur, le PDF joint et, pour un devis, le lien pour l'accepter en ligne.
 */
class CommercialDocumentMail extends Mailable
{
    use Queueable;

    public function __construct(
        public CommercialDocument $document,
        public string $mailSubject,
        public string $body,
        public string $pdf,
        public ?string $acceptUrl,
    ) {
        $this->locale($document->locale);
    }

    public function envelope(): Envelope
    {
        $profile = Profile::query()->first();

        return new Envelope(
            subject: $this->mailSubject,
            // La réponse du client arrive directement dans la boîte du propriétaire.
            replyTo: $profile?->email ? [new Address($profile->email, $profile->name)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.commercial-document',
            with: [
                'isEnglish' => $this->document->locale === 'en',
                'paragraphs' => preg_split('/\R{2,}/', trim($this->body)) ?: [],
                'acceptUrl' => $this->acceptUrl,
                'filename' => $this->document->filename(),
                'ownerName' => Profile::query()->value('name'),
            ],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => $this->pdf, $this->document->filename())->withMime('application/pdf'),
        ];
    }
}

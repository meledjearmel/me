<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\Profile;
use App\Services\AppointmentInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Les emails envoyés au visiteur sur son rendez-vous : demande reçue, confirmé
 * (avec l'invitation .ics) ou refusé. Dates et heures dans son fuseau horaire.
 *
 * Comme l'accusé de réception, il ne reprend aucun texte libre saisi par le
 * visiteur (son message), pour ne pas servir de relais à du spam.
 */
class AppointmentVisitorMail extends Mailable
{
    use Queueable;

    /**
     * @param  'requested'|'confirmed'|'declined'  $kind
     */
    public function __construct(public Appointment $appointment, public string $kind)
    {
        $this->locale($appointment->locale);
    }

    public function envelope(): Envelope
    {
        $profile = Profile::query()->first(['name', 'email']);
        $owner = $profile?->name ?? config('app.name');
        $isEnglish = $this->appointment->locale === 'en';

        $subject = match ($this->kind) {
            'confirmed' => $isEnglish ? "Appointment confirmed — {$owner}" : "Rendez-vous confirmé — {$owner}",
            'declined' => $isEnglish ? "About your appointment request — {$owner}" : "Votre demande de rendez-vous — {$owner}",
            default => $isEnglish ? "Appointment request received — {$owner}" : "Demande de rendez-vous bien reçue — {$owner}",
        };

        return new Envelope(
            subject: $subject,
            // Si le visiteur répond, sa réponse arrive au propriétaire.
            replyTo: $profile?->email ? [new Address($profile->email, $owner)] : [],
        );
    }

    public function content(): Content
    {
        $appointment = $this->appointment->loadMissing('appointmentType');
        $timezone = $appointment->timezone ?: 'Africa/Abidjan';
        $startsAt = $appointment->starts_at->copy()->setTimezone($timezone)->locale($appointment->locale);
        $endsAt = $appointment->ends_at->copy()->setTimezone($timezone);

        return new Content(
            markdown: 'mail.appointment-visitor',
            with: [
                'isEnglish' => $appointment->locale === 'en',
                'ownerName' => Profile::query()->value('name') ?? config('app.name'),
                'typeName' => $appointment->appointmentType?->getTranslation('name', $appointment->locale),
                'when' => $startsAt->isoFormat('dddd D MMMM YYYY, HH:mm').' – '.$endsAt->format('H:i'),
                'timezone' => $timezone,
            ],
        );
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        if ($this->kind !== 'confirmed') {
            return [];
        }

        $ics = app(AppointmentInvite::class)->ics($this->appointment);

        return [
            Attachment::fromData(fn (): string => $ics, 'rendez-vous.ics')->withMime('text/calendar; charset=utf-8; method=REQUEST'),
        ];
    }
}

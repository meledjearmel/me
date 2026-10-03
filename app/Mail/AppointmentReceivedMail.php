<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Notification envoyée au propriétaire du site quand une demande de rendez-vous arrive ou est annulée par le visiteur. */
class AppointmentReceivedMail extends Mailable
{
    use Queueable;

    /**
     * @param  'requested'|'cancelled'  $event
     */
    public function __construct(public Appointment $appointment, public string $event = 'requested') {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->event === 'cancelled'
                ? "Rendez-vous annulé : {$this->appointment->name}"
                : "Demande de rendez-vous : {$this->appointment->name}",
            replyTo: [new Address($this->appointment->email, $this->appointment->name)],
        );
    }

    public function content(): Content
    {
        $appointment = $this->appointment->loadMissing('appointmentType');

        return new Content(
            markdown: 'mail.appointment-received',
            with: [
                'appointment' => $appointment,
                'event' => $this->event,
                // Mes heures : Abidjan (le fuseau de l'application).
                'when' => $appointment->starts_at->copy()->locale('fr')->isoFormat('dddd D MMMM YYYY, HH:mm').' – '.$appointment->ends_at->format('H:i'),
            ],
        );
    }
}

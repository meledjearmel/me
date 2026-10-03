<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Notification envoyée au propriétaire du site quand une demande de rendez-vous arrive. */
class AppointmentReceivedMail extends Mailable
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Demande de rendez-vous : {$this->appointment->name}",
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
                // Mes heures : Abidjan (le fuseau de l'application).
                'when' => $appointment->starts_at->copy()->locale('fr')->isoFormat('dddd D MMMM YYYY, HH:mm').' – '.$appointment->ends_at->format('H:i'),
            ],
        );
    }
}

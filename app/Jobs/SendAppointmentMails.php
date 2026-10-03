<?php

namespace App\Jobs;

use App\Mail\AppointmentReceivedMail;
use App\Mail\AppointmentVisitorMail;
use App\Models\Appointment;
use App\Models\Profile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envoie les emails d'un rendez-vous en tâche de fond :
 * - demande reçue : accusé au visiteur et notification au propriétaire ;
 * - confirmé, refusé ou rappel de la veille : un email au visiteur ;
 * - annulé par le visiteur : confirmation au visiteur et notification au propriétaire.
 * Un échec d'envoi est journalisé et ne casse jamais la demande, déjà enregistrée.
 */
class SendAppointmentMails implements ShouldQueue
{
    use Dispatchable, Queueable;

    /** Un seul essai : un second passage renverrait l'email au visiteur en double. */
    public int $tries = 1;

    /**
     * @param  'requested'|'confirmed'|'declined'|'cancelled'|'reminder'  $event
     */
    public function __construct(public int $appointmentId, public string $event) {}

    public function handle(): void
    {
        $appointment = Appointment::query()->with('appointmentType')->find($this->appointmentId);

        if ($appointment === null) {
            return;
        }

        $this->send($appointment, fn () => Mail::to($appointment->email, $appointment->name)
            ->send(new AppointmentVisitorMail($appointment, $this->event)));

        $ownerEmail = Profile::query()->value('email');

        if (in_array($this->event, ['requested', 'cancelled'], true) && $ownerEmail !== null) {
            $this->send($appointment, fn () => Mail::to($ownerEmail)->send(new AppointmentReceivedMail($appointment, $this->event)));
        }
    }

    private function send(Appointment $appointment, callable $sending): void
    {
        try {
            $sending();
        } catch (Throwable $exception) {
            Log::error('Email de rendez-vous impossible', [
                'appointment' => $appointment->id,
                'event' => $this->event,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

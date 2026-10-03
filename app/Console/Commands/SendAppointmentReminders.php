<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Jobs\SendAppointmentMails;
use App\Models\Appointment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Rappelle au visiteur son rendez-vous confirmé, la veille (dans les 24 h qui
 * viennent). Planifiée toutes les heures ; un rendez-vous n'est rappelé qu'une fois.
 */
#[Signature('appointments:send-reminders')]
#[Description('Envoie le rappel des rendez-vous confirmés des prochaines 24 heures')]
class SendAppointmentReminders extends Command
{
    public function handle(): int
    {
        $appointments = Appointment::query()
            ->where('status', AppointmentStatus::Confirmed)
            ->whereNull('reminded_at')
            ->whereBetween('starts_at', [now(), now()->addDay()])
            ->get();

        foreach ($appointments as $appointment) {
            $appointment->forceFill(['reminded_at' => now()])->save();
            SendAppointmentMails::dispatch($appointment->id, 'reminder');
        }

        $this->info("{$appointments->count()} rappel(s) envoyé(s).");

        return self::SUCCESS;
    }
}

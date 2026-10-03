<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Profile;

/**
 * Invitation calendrier (.ics) d'un rendez-vous confirmé : le visiteur l'ajoute
 * à son agenda (Google, Outlook, Apple) en un clic, dans son propre fuseau.
 */
class AppointmentInvite
{
    public function ics(Appointment $appointment): string
    {
        $profile = Profile::query()->first(['name', 'email']);
        $owner = $profile?->name ?? (string) config('app.name');
        $type = $appointment->appointmentType?->getTranslation('name', $appointment->locale) ?? 'Rendez-vous';

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.$this->escape($owner).'//Rendez-vous//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'UID:appointment-'.$appointment->id.'@'.parse_url((string) config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$appointment->starts_at->copy()->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$appointment->ends_at->copy()->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$this->escape("{$type} — {$owner}"),
            'STATUS:CONFIRMED',
        ];

        if (filled($appointment->meeting_details)) {
            $lines[] = 'DESCRIPTION:'.$this->escape($appointment->meeting_details);

            if (filter_var($appointment->meeting_details, FILTER_VALIDATE_URL)) {
                $lines[] = 'LOCATION:'.$this->escape($appointment->meeting_details);
                $lines[] = 'URL:'.$appointment->meeting_details;
            }
        }

        if ($profile?->email) {
            $lines[] = 'ORGANIZER;CN='.$this->escape($owner).':mailto:'.$profile->email;
        }

        $lines[] = 'ATTENDEE;CN='.$this->escape($appointment->name).';RSVP=FALSE:mailto:'.$appointment->email;
        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }

    /** Échappement des valeurs texte selon la RFC 5545. */
    private function escape(string $value): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $value);
    }
}

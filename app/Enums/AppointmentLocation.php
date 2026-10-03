<?php

namespace App\Enums;

enum AppointmentLocation: string
{
    case Video = 'video';
    case Phone = 'phone';
    case Whatsapp = 'whatsapp';
    case InPerson = 'in_person';

    /** Le visiteur doit laisser son numéro pour un appel téléphonique ou WhatsApp. */
    public function needsPhone(): bool
    {
        return in_array($this, [self::Phone, self::Whatsapp], true);
    }
}

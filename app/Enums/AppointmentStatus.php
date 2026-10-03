<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Declined = 'declined';
    case Cancelled = 'cancelled';

    /** Une demande en attente ou confirmée occupe son créneau dans l'agenda. */
    public function holdsSlot(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed], true);
    }
}
